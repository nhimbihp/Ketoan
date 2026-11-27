<?php

namespace App\Services\Einvoice;

use App\Models\EinvoiceBatch;
use App\Models\EinvoiceItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use RuntimeException;

/**
 * Port of DownloadReceipt + DownloadXML_zip (mdHoadondientu.vb), split into short steps
 * so each web request stays well under the hosting time limit:
 *  - listNextPeriod(): one month period, all pages of query + sco-query
 *  - fetchNext(): a few invoices (detail, wait, export-xml, wait)
 */
class EinvoiceDownloader
{
    // Safety guard against an endless "state" paging loop
    private const MAX_PAGES = 500;

    public function __construct(private XmlExtractor $extractor) {}

    /** Collect invoice keys for the next month period. Throws GdtException on AUTH / RATE_LIMIT (step can be retried). */
    public function listNextPeriod(EinvoiceBatch $batch, GdtClient $client): void
    {
        if ($batch->status !== 'listing') {
            return;
        }

        $periods = $batch->periods;
        if ($batch->period_index === 0) {
            $this->pause('before_list');
        }

        [$from, $to] = $periods[$batch->period_index];
        $search = InvoicePeriod::buildSearch($from, $to, $batch->invoice_status, $batch->process_result);

        foreach ([GdtClient::SOURCE_QUERY, GdtClient::SOURCE_SCO] as $source) {
            try {
                $this->collectPages($batch, $client, $source, $search);
            } catch (GdtException $e) {
                if (in_array($e->kind, [GdtException::AUTH, GdtException::RATE_LIMIT])) {
                    throw $e;
                }
                $batch->addError(sprintf('Lỗi tải danh sách hóa đơn %s ngày %s - %s: %s',
                    $source === GdtClient::SOURCE_SCO ? '(máy tính tiền)' : '',
                    $this->vnDate($from), $this->vnDate($to), $e->getMessage()));
            }
        }

        $batch->period_index++;
        if ($batch->period_index >= count($periods)) {
            $batch->status = 'downloading';
            $batch->total = $batch->items()->count();
            if ($batch->total === 0) {
                $batch->status = 'done';
            }
        }
        $batch->save();
    }

    /** Download the next few XML files. Throws GdtException on AUTH. Sets status "paused" on HTTP 429. */
    public function fetchNext(EinvoiceBatch $batch, GdtClient $client): void
    {
        if ($batch->status === 'paused') {
            $batch->status = 'downloading';
        }
        if ($batch->status !== 'downloading') {
            return;
        }

        $items = $batch->items()->where('status', 'pending')->orderBy('id')
            ->limit(config('einvoice.fetch_batch_size'))->get();

        if ($batch->downloaded + $batch->failed === 0 && $items->isNotEmpty()) {
            $this->pause('before_download');
        }

        try {
            foreach ($items as $item) {
                $this->fetchItem($batch, $client, $item);
                $this->pause('between_xml');
            }
        } catch (GdtException $e) {
            if ($e->kind !== GdtException::RATE_LIMIT) {
                $this->refreshCounters($batch);
                $batch->save();
                throw $e;
            }
            // VB stops the whole download on 429; here the user can press "Tải tiếp" later
            $batch->status = 'paused';
            $batch->addError($e->getMessage());
        }

        $this->refreshCounters($batch);
        if ($batch->status === 'downloading' && ! $batch->items()->where('status', 'pending')->exists()) {
            $batch->status = 'done';
        }
        $batch->save();
    }

    private function fetchItem(EinvoiceBatch $batch, GdtClient $client, EinvoiceItem $item): void
    {
        // VB calls /detail for every invoice then waits 500ms; its result is not used, errors are ignored
        try {
            $client->detail($item->source, $item->apiKey());
        } catch (GdtException $e) {
            if (in_array($e->kind, [GdtException::AUTH, GdtException::RATE_LIMIT])) {
                throw $e;
            }
        }
        $this->pause('after_detail');

        try {
            $zip = $client->exportXml($item->source, $item->apiKey());
            $files = $this->extractor->extract($zip, $this->baseName($batch, $item));
        } catch (GdtException $e) {
            if (in_array($e->kind, [GdtException::AUTH, GdtException::RATE_LIMIT])) {
                throw $e;
            }
            $this->markFailed($batch, $item, $e->getMessage());

            return;
        } catch (RuntimeException $e) {
            $this->markFailed($batch, $item, $e->getMessage());

            return;
        }

        if (! $files) {
            $this->markFailed($batch, $item, 'File zip không có file XML.');

            return;
        }

        foreach ($files as $file) {
            Storage::disk('local')->put($batch->storageDir().'/'.$file['name'], $file['content']);
        }
        $item->update(['status' => 'done', 'file_name' => $files[0]['name'], 'error' => null]);
    }

    /** "khhdon_shdon" like VB; prefixed with the seller tax code if that name is already used (same symbol/number from another seller). */
    private function baseName(EinvoiceBatch $batch, EinvoiceItem $item): string
    {
        $name = $this->safe("{$item->khhdon}_{$item->shdon}");
        if (Storage::disk('local')->exists($batch->storageDir()."/{$name}.xml")) {
            $name = $this->safe("{$item->nbmst}_{$item->khhdon}_{$item->shdon}");
        }

        return $name;
    }

    private function markFailed(EinvoiceBatch $batch, EinvoiceItem $item, string $message): void
    {
        $item->update(['status' => 'failed', 'error' => mb_substr($message, 0, 250)]);
        $batch->addError("{$item->label()} (MST {$item->nbmst}): {$message}");
    }

    private function collectPages(EinvoiceBatch $batch, GdtClient $client, string $source, string $search): void
    {
        $state = null;
        $seenStates = [];

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $result = $client->listInvoices($batch->direction, $source, $search, $state);
            $this->storeItems($batch, $source, $result['datas']);

            $state = $result['state'];
            if ($state === null || $state === '' || isset($seenStates[$state])) {
                return;
            }
            $seenStates[$state] = true;
        }
    }

    private function storeItems(EinvoiceBatch $batch, string $source, array $rows): void
    {
        $now = now();
        $records = [];
        foreach ($rows as $row) {
            $records[] = [
                'einvoice_batch_id' => $batch->id,
                'source' => $source,
                'nbmst' => (string) ($row['nbmst'] ?? ''),
                'khhdon' => (string) ($row['khhdon'] ?? ''),
                'shdon' => (string) ($row['shdon'] ?? ''),
                'khmshdon' => (string) ($row['khmshdon'] ?? ''),
                'issued_on' => InvoicePeriod::isoToDate($row['tdlap'] ?? null),
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($records) {
            EinvoiceItem::insertOrIgnore($records);
        }
    }

    private function refreshCounters(EinvoiceBatch $batch): void
    {
        $batch->downloaded = $batch->items()->where('status', 'done')->count();
        $batch->failed = $batch->items()->where('status', 'failed')->count();
    }

    private function pause(string $key): void
    {
        $ms = (int) config("einvoice.delays_ms.{$key}", 0);
        if ($ms > 0) {
            Sleep::for($ms)->milliseconds();
        }
    }

    private function safe(string $name): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
    }

    private function vnDate(string $date): string
    {
        return CarbonImmutable::parse($date)->format('d/m/Y');
    }
}

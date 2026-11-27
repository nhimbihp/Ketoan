<?php

namespace App\Services;

use App\Models\Import;
use App\Models\Voucher;
use App\Support\Money;
use App\Support\VietnameseNumber;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/** Chuẩn bị dữ liệu in và xuất PDF cho một lần import. */
class VoucherPrinter
{
    /**
     * @param  array{0: ?int, 1: ?int}  $range  từ trang – đến trang (null = tất cả)
     */
    public function data(Import $import, array $range): array
    {
        [$from, $to] = $range;
        $type = $import->type();
        $tenant = $import->tenant;

        $vouchers = $import->vouchers()->with('lines')
            ->when($from, fn ($q) => $q->where('page_no', '>=', $from))
            ->when($to, fn ($q) => $q->where('page_no', '<=', $to))
            ->get();

        return [
            'import' => $import,
            'type' => $type,
            'view' => $type->printView(),
            'labels' => $type->labels(),
            'company' => $import->company_name,
            'address' => $import->company_address,
            'decision' => $import->decision,
            'signers' => $tenant?->settings['signers'] ?? [],
            'pages' => $vouchers->map(fn (Voucher $v) => [
                'voucher' => $v,
                'total' => Money::dot($v->total),
                'words' => VietnameseNumber::money($v->total),
            ]),
        ];
    }

    public function pdf(Import $import, array $range): string
    {
        $data = $this->data($import, $range);
        $tmp = storage_path('app/mpdf');
        if (! is_dir($tmp)) {
            mkdir($tmp, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $data['type']->paper(),
            'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
            'margin_header' => 0, 'margin_footer' => 0,
            'default_font' => 'freesans',
            'default_font_size' => 10,
            'tempDir' => $tmp,
            'autoPageBreak' => false,
        ]);
        $mpdf->SetTitle($data['type']->label().' - '.$import->original_filename);
        $mpdf->WriteHTML(view('print.css', ['pdf' => true])->render(), HTMLParserMode::HEADER_CSS);

        foreach ($data['pages'] as $i => $page) {
            if ($i > 0) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML(view($data['view'], $data + $page)->render(), HTMLParserMode::HTML_BODY);
        }
        if ($data['pages']->isEmpty()) {
            $mpdf->WriteHTML('<p style="margin:20mm">Không có phiếu nào trong khoảng trang đã chọn.</p>');
        }

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}

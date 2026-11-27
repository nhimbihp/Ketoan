<?php

namespace App\Services;

use App\Documents\DocumentType;
use App\Models\Decision;
use App\Models\Import;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Upload file Excel → đọc → gộp thành phiếu → lưu CSDL (nút "Thống kê" của bản VFP). */
class VoucherImporter
{
    public function __construct(private ExcelReader $reader) {}

    public function import(UploadedFile $file, DocumentType $type, User $user, string $encoding = 'auto'): Import
    {
        $tenant = $user->tenant;
        $storedPath = $file->store('imports/'.$tenant->id, 'local');

        $result = $this->reader->read(Storage::disk('local')->path($storedPath), $type, $encoding);
        $vouchers = $type->aggregate($result['rows']);

        if ($vouchers === []) {
            throw new RuntimeException('Không tìm thấy dòng dữ liệu hợp lệ (cần có Số phiếu và Số tiền > 0).');
        }

        return DB::transaction(function () use ($file, $type, $user, $tenant, $storedPath, $result, $vouchers) {
            $import = Import::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'document_type' => $type->key(),
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'encoding' => $result['encoding'],
                'row_count' => count($result['rows']),
                'company_name' => $tenant->name,
                'company_address' => $tenant->address,
                'decision_id' => $this->defaultDecisionId($tenant, $type),
            ]);

            foreach ($vouchers as $i => $data) {
                $voucher = $import->vouchers()->create([
                    'tenant_id' => $tenant->id,
                    'page_no' => $i + 1,
                    'number' => $data['number'],
                    'date' => $data['date'],
                    'person' => $data['person'],
                    'department' => $data['department'],
                    'reason' => $data['reason'],
                    'total' => $data['total'],
                    'extra' => $data['extra'] ?? null,
                ]);
                foreach ($data['lines'] as $pos => $line) {
                    $voucher->lines()->create($line + ['position' => $pos]);
                }
            }

            return $import;
        });
    }

    /** Mẫu mặc định của công ty (khớp theo tên, vì mỗi loại chứng từ có mẫu số riêng), không có thì lấy mẫu đầu tiên. */
    private function defaultDecisionId(Tenant $tenant, DocumentType $type): ?int
    {
        $name = $tenant->defaultDecision?->name;
        $query = fn () => Decision::forType($type->key());

        return ($name ? $query()->where('name', $name)->value('id') : null) ?? $query()->value('id');
    }
}

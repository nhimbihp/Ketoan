<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One download run of e-invoices from the tax portal. */
class EinvoiceBatch extends Model
{
    use BelongsToTenant;

    public const DIRECTIONS = ['purchase' => 'Mua vào', 'sold' => 'Bán ra'];

    // tthai, same order as cboReceiptStatus in frmDownloadHD.vb
    public const INVOICE_STATUSES = [
        'all' => 'Tất cả',
        '1' => 'Hóa đơn mới',
        '2' => 'Hóa đơn thay thế',
        '3' => 'Hóa đơn điều chỉnh',
        '4' => 'Hóa đơn bị thay thế',
        '5' => 'Hóa đơn đã bị điều chỉnh',
        '6' => 'Hóa đơn bị hủy',
    ];

    // ttxly, same as cboResult in frmDownloadHD.vb
    public const PROCESS_RESULTS = [
        'purchase' => [
            'all' => 'Tất cả',
            '5' => 'Đã cấp mã hóa đơn',
            '6' => 'Tổng cục thuế đã nhận không mã',
            '8' => 'Tổng cục thuế đã nhận hóa đơn có mã khởi tạo từ máy tính tiền',
        ],
        'sold' => [
            'all' => 'Tất cả',
            '0' => 'Tổng cục thuế đã nhận',
            '1' => 'Đang tiến hành kiểm tra điều kiện cấp mã',
            '2' => 'CQT từ chối theo từng lần phát sinh',
            '3' => 'Hóa đơn đủ điều kiện cấp mã',
            '4' => 'Hóa đơn không đủ điều kiện cấp mã',
            '5' => 'Đã cấp mã hóa đơn',
            '6' => 'Tổng cục thuế đã nhận không mã',
            '7' => 'Đã kiểm tra HĐĐT định kỳ không có mã',
            '8' => 'Tổng cục thuế đã nhận hóa đơn có mã khởi tạo từ máy tính tiền',
        ],
    ];

    public const STATUS_LABELS = [
        'listing' => 'Đang lấy danh sách',
        'downloading' => 'Đang tải XML',
        'paused' => 'Tạm dừng',
        'done' => 'Hoàn tất',
    ];

    private const MAX_ERRORS = 500;

    protected $fillable = [
        'tenant_id', 'user_id', 'direction', 'from_date', 'to_date', 'invoice_status', 'process_result',
        'company_name', 'periods', 'period_index', 'status', 'total', 'downloaded', 'failed', 'errors',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'periods' => 'array',
            'errors' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EinvoiceItem::class);
    }

    /** Folder on the "local" disk holding the XML files. */
    public function storageDir(): string
    {
        return "einvoices/{$this->tenant_id}/{$this->id}";
    }

    public function addError(string $message): void
    {
        $errors = $this->errors ?? [];
        if (count($errors) < self::MAX_ERRORS) {
            $errors[] = $message;
        }
        $this->errors = $errors;
    }

    public function directionLabel(): string
    {
        return self::DIRECTIONS[$this->direction] ?? $this->direction;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isFinished(): bool
    {
        return $this->status === 'done';
    }

    /** Progress 0-100: listing = first 30%, downloading = remaining 70%. */
    public function percent(): int
    {
        if ($this->status === 'done') {
            return 100;
        }
        if ($this->status === 'listing') {
            $count = max(1, count($this->periods ?? []));

            return (int) floor($this->period_index / $count * 30);
        }

        return 30 + (int) floor(($this->downloaded + $this->failed) / max(1, $this->total) * 70);
    }
}

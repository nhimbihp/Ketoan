<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Một phiếu (1 trang in). */
class Voucher extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'import_id', 'page_no', 'number', 'date', 'person', 'department', 'reason', 'total', 'extra'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total' => 'float',
            'extra' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(VoucherLine::class)->orderBy('position');
    }
}

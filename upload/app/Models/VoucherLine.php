<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['voucher_id', 'position', 'role', 'account', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}

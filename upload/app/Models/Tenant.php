<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Một khách hàng (công ty) dùng dịch vụ. */
class Tenant extends Model
{
    public const SIGNERS = [
        'director' => 'Giám đốc',
        'chief_accountant' => 'Kế toán trưởng',
        'cashier' => 'Thủ quỹ',
        'preparer' => 'Người lập',
    ];

    protected $fillable = ['name', 'address', 'tax_code', 'phone', 'status', 'expires_at', 'default_decision_id', 'settings'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    public function defaultDecision(): BelongsTo
    {
        return $this->belongsTo(Decision::class, 'default_decision_id')->withoutGlobalScopes();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && (! $this->expires_at || $this->expires_at->endOfDay()->isFuture());
    }

    public function signer(string $key): ?string
    {
        return $this->settings['signers'][$key] ?? null;
    }
}

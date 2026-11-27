<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/** Mẫu số + Quyết định/Thông tư ban hành (thay QuyetDinh.dbf). tenant_id null = dùng chung. */
class Decision extends Model
{
    protected $fillable = ['tenant_id', 'document_type', 'name', 'form_code', 'decision_text', 'date_text', 'sort'];

    protected static function booted(): void
    {
        // Khách hàng thấy mẫu dùng chung + mẫu riêng của mình
        static::addGlobalScope('tenant', function (Builder $query) {
            if (Auth::check()) {
                $query->where(fn ($q) => $q->whereNull('decisions.tenant_id')
                    ->orWhere('decisions.tenant_id', Auth::user()->tenant_id ?? 0));
            }
        });
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('document_type')->orWhere('document_type', $type))
            ->orderBy('sort')->orderBy('id');
    }
}

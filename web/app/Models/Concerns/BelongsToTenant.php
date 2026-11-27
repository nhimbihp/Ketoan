<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Tách dữ liệu theo khách hàng: mọi truy vấn tự lọc theo tenant của người đang đăng nhập,
 * bản ghi mới tự gán tenant_id. Người đăng nhập không thuộc khách hàng nào không thấy gì.
 * Khi không có người đăng nhập (console, seeder) thì không lọc.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (! Auth::check()) {
                return;
            }
            $column = $query->getModel()->getTable().'.tenant_id';
            $tenantId = Auth::user()->tenant_id;
            $tenantId ? $query->where($column, $tenantId) : $query->whereRaw('1 = 0');
        });

        static::creating(function ($model) {
            if (! $model->tenant_id && Auth::check()) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

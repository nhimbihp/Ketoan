<?php

namespace App\Models;

use App\Documents\DocumentRegistry;
use App\Documents\DocumentType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Một lần upload file Excel + "Thống kê". */
class Import extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'document_type', 'original_filename', 'stored_path', 'encoding',
        'row_count', 'company_name', 'company_address', 'decision_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decision(): BelongsTo
    {
        return $this->belongsTo(Decision::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class)->orderBy('page_no');
    }

    public function type(): DocumentType
    {
        return app(DocumentRegistry::class)->get($this->document_type);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One invoice found in a download run. Access goes through EinvoiceBatch (tenant scoped). */
class EinvoiceItem extends Model
{
    protected $fillable = [
        'einvoice_batch_id', 'source', 'nbmst', 'khhdon', 'shdon', 'khmshdon', 'issued_on', 'status', 'file_name', 'error',
    ];

    protected function casts(): array
    {
        return ['issued_on' => 'date'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(EinvoiceBatch::class, 'einvoice_batch_id');
    }

    /** Key fields used by detail / export-xml. */
    public function apiKey(): array
    {
        return ['nbmst' => $this->nbmst, 'khhdon' => $this->khhdon, 'shdon' => $this->shdon, 'khmshdon' => $this->khmshdon];
    }

    public function label(): string
    {
        return "{$this->khhdon}_{$this->shdon}";
    }
}

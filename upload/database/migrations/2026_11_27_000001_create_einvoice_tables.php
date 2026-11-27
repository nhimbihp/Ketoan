<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One download run from the tax portal (form frmDownloadHD)
        Schema::create('einvoice_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 10);              // purchase | sold
            $table->date('from_date');
            $table->date('to_date');
            $table->string('invoice_status', 10)->default('all');  // tthai
            $table->string('process_result', 10)->default('all');  // ttxly
            $table->string('company_name')->nullable();
            $table->json('periods');                       // month periods [[from, to], ...]
            $table->unsignedInteger('period_index')->default(0);
            $table->string('status', 20)->default('listing'); // listing | downloading | paused | done
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('downloaded')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
        });

        // One invoice found in the list
        Schema::create('einvoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('einvoice_batch_id')->constrained()->cascadeOnDelete();
            $table->string('source', 10);       // query | sco-query
            $table->string('nbmst', 20);        // seller tax code
            $table->string('khhdon', 20);       // invoice symbol
            $table->string('shdon', 20);        // invoice number
            $table->string('khmshdon', 5);      // form number
            $table->date('issued_on')->nullable();
            $table->string('status', 10)->default('pending'); // pending | done | failed
            $table->string('file_name')->nullable();
            $table->string('error')->nullable();
            $table->timestamps();
            $table->unique(['einvoice_batch_id', 'source', 'nbmst', 'khhdon', 'shdon', 'khmshdon'], 'einvoice_items_key');
            $table->index(['einvoice_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('einvoice_items');
        Schema::dropIfExists('einvoice_batches');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('tax_code', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('active'); // active | suspended
            $table->date('expires_at')->nullable();
            $table->unsignedBigInteger('default_decision_id')->nullable();
            $table->json('settings')->nullable(); // tên người ký...
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('staff')->after('email'); // super_admin | owner | staff
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['role', 'is_active']);
        });
        Schema::dropIfExists('tenants');
    }
};

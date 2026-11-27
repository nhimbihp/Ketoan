<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mẫu số / Quyết định ban hành in ở góc phải phiếu (thay QuyetDinh.dbf)
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete(); // null = dùng chung
            $table->string('document_type', 50)->nullable(); // null = mọi loại chứng từ
            $table->string('name');
            $table->string('form_code');
            $table->string('decision_text');
            $table->string('date_text');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Mỗi lần upload Excel + "Thống kê"
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type', 50);
            $table->string('original_filename');
            $table->string('stored_path')->nullable();
            $table->string('encoding', 20)->default('unicode');
            $table->unsignedInteger('row_count')->default(0);
            $table->string('company_name')->nullable();
            $table->string('company_address')->nullable();
            $table->foreignId('decision_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Mỗi phiếu = 1 trang in
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page_no');
            $table->string('number');
            $table->date('date')->nullable();
            $table->string('person')->nullable();
            $table->string('department')->nullable();
            $table->text('reason')->nullable();
            $table->decimal('total', 18, 2)->default(0);
            $table->json('extra')->nullable();
            $table->timestamps();
            $table->index(['import_id', 'page_no']);
        });

        // Bảng tài khoản Nợ/Có bên phải phiếu
        Schema::create('voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('role', 20); // counter = TK tiền mặt (111) ; detail = TK đối ứng
            $table->string('account', 30);
            $table->decimal('amount', 18, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_lines');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('imports');
        Schema::dropIfExists('decisions');
    }
};

<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

// Tạo / đặt lại tài khoản quản trị hệ thống: php artisan thuchi:admin email@domain.vn "Tên"
Artisan::command('thuchi:admin {email} {name=Quản trị hệ thống}', function (string $email, string $name) {
    $password = $this->secret('Mật khẩu (ít nhất 8 ký tự)');
    if (! $password || mb_strlen($password) < 8) {
        $this->error('Mật khẩu phải có ít nhất 8 ký tự.');

        return 1;
    }
    User::updateOrCreate(['email' => $email], [
        'name' => $name, 'password' => $password, 'role' => 'super_admin', 'tenant_id' => null, 'is_active' => true,
    ]);
    $this->info("Đã tạo quản trị hệ thống: $email");
})->purpose('Tạo tài khoản quản trị hệ thống (quản lý khách hàng)');

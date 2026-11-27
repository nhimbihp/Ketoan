<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DecisionSeeder::class);

        // Dữ liệu demo chỉ tạo ở môi trường local
        if (app()->environment('local')) {
            User::updateOrCreate(['email' => 'admin@example.test'], [
                'name' => 'Quản trị hệ thống', 'password' => 'password', 'role' => 'super_admin', 'is_active' => true,
            ]);

            $tenant = Tenant::updateOrCreate(['name' => 'Công ty TNHH Demo'], [
                'address' => '123 Đường ABC, Quận 1, TP.HCM', 'status' => 'active',
            ]);
            User::updateOrCreate(['email' => 'demo@example.test'], [
                'name' => 'Chủ tài khoản Demo', 'password' => 'password', 'role' => 'owner',
                'tenant_id' => $tenant->id, 'is_active' => true,
            ]);
        }
    }
}

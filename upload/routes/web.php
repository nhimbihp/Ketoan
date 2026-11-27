<?php

use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Trang chủ sau đăng nhập
Route::get('/dashboard', function () {
    $user = auth()->user();

    return $user->isSuperAdmin() && ! $user->tenant_id
        ? redirect()->route('admin.tenants.index')
        : redirect()->route('imports.create');
})->middleware('auth')->name('dashboard');

// Lập & in chứng từ (theo khách hàng)
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/phieu', [ImportController::class, 'index'])->name('imports.index');
    Route::get('/phieu/lap', [ImportController::class, 'create'])->name('imports.create');
    Route::post('/phieu', [ImportController::class, 'store'])->name('imports.store');
    Route::get('/phieu/{import}', [ImportController::class, 'show'])->name('imports.show');
    Route::delete('/phieu/{import}', [ImportController::class, 'destroy'])->name('imports.destroy');

    Route::post('/phieu/{import}/in', [PrintController::class, 'prepare'])->name('imports.print.prepare');
    Route::get('/phieu/{import}/in', [PrintController::class, 'show'])->name('imports.print');
    Route::get('/phieu/{import}/pdf', [PrintController::class, 'pdf'])->name('imports.pdf');

    // Chủ tài khoản quản lý công ty
    Route::middleware('can:manage-company')->group(function () {
        Route::get('/cong-ty', [CompanyController::class, 'edit'])->name('company.edit');
        Route::put('/cong-ty', [CompanyController::class, 'update'])->name('company.update');
        Route::post('/cong-ty/nguoi-dung', [CompanyController::class, 'storeUser'])->name('company.users.store');
        Route::patch('/cong-ty/nguoi-dung/{user}', [CompanyController::class, 'toggleUser'])->name('company.users.toggle');
        Route::post('/cong-ty/mau', [CompanyController::class, 'storeDecision'])->name('company.decisions.store');
        Route::delete('/cong-ty/mau/{decision}', [CompanyController::class, 'destroyDecision'])->name('company.decisions.destroy');
    });
});

// Quản trị hệ thống: quản lý khách hàng
Route::middleware(['auth', 'can:manage-tenants'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('tenants', TenantController::class)->except(['show', 'destroy']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Quản trị hệ thống: tạo, sửa, khoá, gia hạn khách hàng. */
class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::withCount(['users', 'imports'])->orderBy('name')->paginate(30);

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        return view('admin.tenants.form', ['tenant' => new Tenant(['status' => 'active'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules() + [
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', Password::defaults()],
        ], [], $this->attributes());

        DB::transaction(function () use ($data) {
            $tenant = Tenant::create($this->tenantData($data));
            User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => $data['owner_password'],
                'role' => 'owner',
                'is_active' => true,
            ]);
        });

        return redirect()->route('admin.tenants.index')->with('status', 'Đã tạo khách hàng '.$data['name'].'.');
    }

    public function edit(Tenant $tenant): View
    {
        $tenant->load('users');

        return view('admin.tenants.form', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate($this->rules(), [], $this->attributes());
        $tenant->update($this->tenantData($data));

        return redirect()->route('admin.tenants.index')->with('status', 'Đã cập nhật '.$tenant->name.'.');
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    private function attributes(): array
    {
        return [
            'name' => 'tên công ty', 'status' => 'trạng thái', 'expires_at' => 'ngày hết hạn',
            'owner_name' => 'họ tên chủ tài khoản', 'owner_email' => 'email chủ tài khoản',
            'owner_password' => 'mật khẩu',
        ];
    }

    private function tenantData(array $data): array
    {
        return array_intersect_key($data, array_flip(['name', 'address', 'tax_code', 'phone', 'status', 'expires_at']));
    }
}

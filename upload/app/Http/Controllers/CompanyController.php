<?php

namespace App\Http\Controllers;

use App\Documents\DocumentRegistry;
use App\Models\Decision;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Chủ tài khoản: thông tin công ty, người ký, người dùng, mẫu quyết định riêng. */
class CompanyController extends Controller
{
    public function edit(Request $request, DocumentRegistry $registry): View
    {
        $tenant = $request->user()->tenant;

        return view('company.edit', [
            'tenant' => $tenant,
            'users' => $tenant->users()->orderBy('name')->get(),
            'decisions' => Decision::orderBy('sort')->orderBy('id')->get(),
            'types' => $registry->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'default_decision_id' => ['nullable', 'integer'],
            'signers' => ['array'],
            'signers.*' => ['nullable', 'string', 'max:100'],
        ], [], ['name' => 'tên công ty']);

        $decisionId = isset($data['default_decision_id']) ? Decision::find($data['default_decision_id'])?->id : null;
        $signers = array_intersect_key($data['signers'] ?? [], Tenant::SIGNERS);

        $tenant->update([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'tax_code' => $data['tax_code'] ?? null,
            'phone' => $data['phone'] ?? null,
            'default_decision_id' => $decisionId,
            'settings' => array_merge($tenant->settings ?? [], ['signers' => array_filter($signers)]),
        ]);

        return back()->with('status', 'Đã lưu thông tin công ty.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(['owner', 'staff'])],
        ], [], ['name' => 'họ tên', 'password' => 'mật khẩu', 'role' => 'vai trò']);

        User::create($data + ['tenant_id' => $request->user()->tenant_id, 'is_active' => true]);

        return back()->with('status', 'Đã thêm người dùng.');
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->tenant_id === $request->user()->tenant_id, 404);
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Không thể tự khoá tài khoản của mình.']);
        }
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', $user->is_active ? 'Đã mở khoá người dùng.' : 'Đã khoá người dùng.');
    }

    public function storeDecision(Request $request, DocumentRegistry $registry): RedirectResponse
    {
        $data = $request->validate([
            'document_type' => ['nullable', Rule::in(array_keys($registry->all()))],
            'name' => ['required', 'string', 'max:255'],
            'form_code' => ['required', 'string', 'max:255'],
            'decision_text' => ['required', 'string', 'max:255'],
            'date_text' => ['required', 'string', 'max:255'],
        ], [], [
            'name' => 'tên hiển thị', 'form_code' => 'mẫu số',
            'decision_text' => 'quyết định / thông tư', 'date_text' => 'ngày ban hành',
        ]);

        Decision::create($data + ['tenant_id' => $request->user()->tenant_id, 'sort' => 100]);

        return back()->with('status', 'Đã thêm mẫu.');
    }

    public function destroyDecision(Request $request, Decision $decision): RedirectResponse
    {
        abort_unless($decision->tenant_id === $request->user()->tenant_id, 403, 'Không xoá được mẫu dùng chung.');
        $decision->delete();

        return back()->with('status', 'Đã xoá mẫu.');
    }
}

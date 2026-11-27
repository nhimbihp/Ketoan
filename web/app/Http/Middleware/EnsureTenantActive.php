<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Chỉ cho vào khu vực lập phiếu khi người dùng thuộc 1 khách hàng đang hoạt động. */
class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user->is_active) {
            return $this->kick($request, 'Tài khoản của bạn đã bị khoá.');
        }

        if (! $user->tenant_id) {
            return $user->isSuperAdmin()
                ? redirect()->route('admin.tenants.index')
                : $this->kick($request, 'Tài khoản chưa được gán công ty.');
        }

        if (! $user->tenant?->isActive()) {
            return $this->kick($request, 'Tài khoản công ty đã bị khoá hoặc hết hạn sử dụng. Vui lòng liên hệ nhà cung cấp.');
        }

        return $next($request);
    }

    private function kick(Request $request, string $message): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}

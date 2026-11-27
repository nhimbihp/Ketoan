<x-app-layout :title="$tenant->exists ? $tenant->name : 'Thêm khách hàng'">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $tenant->exists ? 'Sửa khách hàng: '.$tenant->name : 'Thêm khách hàng' }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="POST" action="{{ $tenant->exists ? route('admin.tenants.update', $tenant) : route('admin.tenants.store') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @if ($tenant->exists) @method('PUT') @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" value="Tên công ty" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $tenant->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="address" value="Địa chỉ" />
                        <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $tenant->address)" />
                    </div>
                    <div>
                        <x-input-label for="tax_code" value="Mã số thuế" />
                        <x-text-input id="tax_code" name="tax_code" class="mt-1 block w-full" :value="old('tax_code', $tenant->tax_code)" />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Điện thoại" />
                        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $tenant->phone)" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Trạng thái" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="active" @selected(old('status', $tenant->status) === 'active')>Hoạt động</option>
                            <option value="suspended" @selected(old('status', $tenant->status) === 'suspended')>Khoá</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="expires_at" value="Hết hạn sử dụng (bỏ trống = không giới hạn)" />
                        <x-text-input id="expires_at" name="expires_at" type="date" class="mt-1 block w-full" :value="old('expires_at', $tenant->expires_at?->format('Y-m-d'))" />
                        <x-input-error :messages="$errors->get('expires_at')" class="mt-1" />
                    </div>
                </div>

                @unless ($tenant->exists)
                    <h3 class="font-semibold text-gray-900 pt-2">Tài khoản chủ (đăng nhập của khách hàng)</h3>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="owner_name" value="Họ tên" />
                            <x-text-input id="owner_name" name="owner_name" class="mt-1 block w-full" :value="old('owner_name')" required />
                            <x-input-error :messages="$errors->get('owner_name')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="owner_email" value="Email" />
                            <x-text-input id="owner_email" name="owner_email" type="email" class="mt-1 block w-full" :value="old('owner_email')" required />
                            <x-input-error :messages="$errors->get('owner_email')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="owner_password" value="Mật khẩu" />
                            <x-text-input id="owner_password" name="owner_password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('owner_password')" class="mt-1" />
                        </div>
                    </div>
                @endunless

                <div class="flex gap-3">
                    <x-primary-button>Lưu</x-primary-button>
                    <a href="{{ route('admin.tenants.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Huỷ</a>
                </div>
            </form>

            @if ($tenant->exists)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-3">Người dùng</h3>
                    <ul class="text-sm divide-y">
                        @foreach ($tenant->users as $u)
                            <li class="py-2 flex justify-between">
                                <span>{{ $u->name }} · {{ $u->email }}</span>
                                <span class="text-gray-500">{{ \App\Models\User::ROLES[$u->role] ?? $u->role }}{{ $u->is_active ? '' : ' (đã khoá)' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

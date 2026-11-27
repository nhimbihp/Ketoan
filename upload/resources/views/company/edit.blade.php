<x-app-layout title="Công ty">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Thông tin công ty</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif

            {{-- Thông tin công ty + người ký --}}
            <form method="POST" action="{{ route('company.update') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" value="Tên công ty (in trên phiếu)" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $tenant->name)" required />
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
                    <div class="sm:col-span-2">
                        <x-input-label for="default_decision_id" value="Quyết định / Mẫu số mặc định" />
                        <select id="default_decision_id" name="default_decision_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">— Mẫu đầu tiên của từng loại —</option>
                            @foreach ($decisions->unique('name') as $d)
                                <option value="{{ $d->id }}" @selected(old('default_decision_id', $tenant->default_decision_id) == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h3 class="font-semibold text-gray-900 pt-2">Người ký (in sẵn họ tên dưới chữ ký, có thể bỏ trống)</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (\App\Models\Tenant::SIGNERS as $key => $label)
                        <div>
                            <x-input-label :for="'signer_'.$key" :value="$label" />
                            <x-text-input :id="'signer_'.$key" :name="'signers['.$key.']'" class="mt-1 block w-full" :value="old('signers.'.$key, $tenant->signer($key))" />
                        </div>
                    @endforeach
                </div>
                <x-primary-button>Lưu</x-primary-button>
            </form>

            {{-- Người dùng --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Người dùng</h3>
                <table class="min-w-full text-sm mb-6">
                    <thead class="text-left text-gray-500"><tr><th class="py-1">Họ tên</th><th>Email</th><th>Vai trò</th><th></th></tr></thead>
                    <tbody class="divide-y">
                        @foreach ($users as $u)
                            <tr>
                                <td class="py-2">{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>{{ \App\Models\User::ROLES[$u->role] ?? $u->role }}</td>
                                <td class="text-right">
                                    @unless ($u->is(auth()->user()))
                                        <form method="POST" action="{{ route('company.users.toggle', $u) }}">
                                            @csrf @method('PATCH')
                                            <button class="text-xs {{ $u->is_active ? 'text-red-600' : 'text-green-700' }} hover:underline">{{ $u->is_active ? 'Khoá' : 'Mở khoá' }}</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <form method="POST" action="{{ route('company.users.store') }}" class="grid gap-3 sm:grid-cols-5 items-end">
                    @csrf
                    <div><x-input-label for="u_name" value="Họ tên" /><x-text-input id="u_name" name="name" class="mt-1 block w-full" required /></div>
                    <div><x-input-label for="u_email" value="Email" /><x-text-input id="u_email" name="email" type="email" class="mt-1 block w-full" required /></div>
                    <div><x-input-label for="u_password" value="Mật khẩu" /><x-text-input id="u_password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" /></div>
                    <div>
                        <x-input-label for="u_role" value="Vai trò" />
                        <select id="u_role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="staff">Nhân viên</option>
                            <option value="owner">Chủ tài khoản</option>
                        </select>
                    </div>
                    <x-primary-button class="justify-center">Thêm</x-primary-button>
                </form>
            </div>

            {{-- Mẫu quyết định --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Mẫu số / Quyết định ban hành</h3>
                <table class="min-w-full text-sm mb-6">
                    <thead class="text-left text-gray-500"><tr><th class="py-1">Tên</th><th>Loại</th><th>Mẫu số</th><th>Nội dung</th><th></th></tr></thead>
                    <tbody class="divide-y">
                        @foreach ($decisions as $d)
                            <tr>
                                <td class="py-2">{{ $d->name }}</td>
                                <td>{{ $d->document_type ? ($types[$d->document_type] ?? null)?->label() : 'Tất cả' }}</td>
                                <td>{{ $d->form_code }}</td>
                                <td class="text-xs text-gray-600">{{ $d->decision_text }}<br>{{ $d->date_text }}</td>
                                <td class="text-right">
                                    @if ($d->tenant_id)
                                        <form method="POST" action="{{ route('company.decisions.destroy', $d) }}" onsubmit="return confirm('Xoá mẫu này?')">
                                            @csrf @method('DELETE')
                                            <button class="text-xs text-red-600 hover:underline">Xoá</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">Dùng chung</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <form method="POST" action="{{ route('company.decisions.store') }}" class="grid gap-3 sm:grid-cols-3 items-end">
                    @csrf
                    <div><x-input-label for="d_name" value="Tên hiển thị" /><x-text-input id="d_name" name="name" class="mt-1 block w-full" required /></div>
                    <div>
                        <x-input-label for="d_type" value="Loại chứng từ" />
                        <select id="d_type" name="document_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Tất cả</option>
                            @foreach ($types as $key => $t)<option value="{{ $key }}">{{ $t->label() }}</option>@endforeach
                        </select>
                    </div>
                    <div><x-input-label for="d_form" value="Mẫu số" /><x-text-input id="d_form" name="form_code" class="mt-1 block w-full" placeholder="Mẫu số: 01-TT" required /></div>
                    <div><x-input-label for="d_text" value="Quyết định / Thông tư" /><x-text-input id="d_text" name="decision_text" class="mt-1 block w-full" placeholder="Ban hành theo Thông tư số ..." required /></div>
                    <div><x-input-label for="d_date" value="Ngày ban hành" /><x-text-input id="d_date" name="date_text" class="mt-1 block w-full" placeholder="Ngày ... của Bộ Tài chính" required /></div>
                    <x-primary-button class="justify-center">Thêm mẫu</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

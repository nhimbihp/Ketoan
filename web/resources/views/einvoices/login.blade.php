<x-app-layout title="Đăng nhập cổng hóa đơn điện tử">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Đăng nhập cổng hóa đơn điện tử</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('einvoices.login.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-5"
                  x-data="{ show: false }">
                @csrf
                <p class="text-sm text-gray-600">Dùng tài khoản đăng nhập <b>hoadondientu.gdt.gov.vn</b>. Mật khẩu chỉ dùng để đăng nhập, không lưu lại.</p>

                <div>
                    <x-input-label for="username" value="Tên đăng nhập (mã số thuế)" />
                    <x-text-input id="username" name="username" class="mt-1 block w-full" :value="old('username')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Mật khẩu" />
                    <div class="mt-1 flex gap-2">
                        <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password"
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="button" @click="show = !show" class="px-3 text-sm border rounded-md text-gray-600 hover:bg-gray-50"
                                x-text="show ? 'Ẩn' : 'Hiện'"></button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="captcha" value="Mã captcha" />
                    @if ($captchaSvg)
                        <div class="mt-1 flex items-center gap-3">
                            {{-- SVG shown via <img> so any script inside cannot run --}}
                            <img src="data:image/svg+xml;base64,{{ base64_encode($captchaSvg) }}" alt="captcha" class="h-14 border rounded bg-white">
                            <a href="{{ route('einvoices.login') }}" class="text-sm text-indigo-700 hover:underline">Lấy mã khác</a>
                        </div>
                    @else
                        <div class="mt-1 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                            Không lấy được captcha: {{ $captchaError }}
                            <a href="{{ route('einvoices.login') }}" class="underline">Thử lại</a>
                        </div>
                    @endif
                    <x-text-input id="captcha" name="captcha" class="mt-2 block w-48" required autocomplete="off" />
                    <x-input-error :messages="$errors->get('captcha')" class="mt-2" />
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>Đăng nhập</x-primary-button>
                    <a href="{{ route('einvoices.index') }}" class="text-sm text-gray-600 hover:underline">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<x-app-layout title="Kiểm tra kết nối cổng thuế">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kiểm tra kết nối cổng hóa đơn điện tử</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3 text-sm">
                <div class="rounded-md px-4 py-3 {{ $ok ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' }}">
                    {{ $ok ? 'Hosting gọi được API cổng thuế.' : 'Hosting KHÔNG gọi được API cổng thuế.' }}
                </div>
                <table class="w-full">
                    <tr class="border-t"><td class="py-1 pr-4 text-gray-500">URL</td><td class="py-1 font-mono break-all">{{ $url }}</td></tr>
                    <tr class="border-t"><td class="py-1 pr-4 text-gray-500">HTTP</td><td class="py-1">{{ $status ?? '-' }}</td></tr>
                    <tr class="border-t"><td class="py-1 pr-4 text-gray-500">Thời gian</td><td class="py-1">{{ $seconds }} giây</td></tr>
                    <tr class="border-t"><td class="py-1 pr-4 text-gray-500">cURL</td><td class="py-1">{{ $curl }}</td></tr>
                    <tr class="border-t"><td class="py-1 pr-4 text-gray-500">Lỗi</td><td class="py-1 break-all">{{ $error ?? '-' }}</td></tr>
                    <tr class="border-t"><td class="py-1 pr-4 text-gray-500">Nội dung</td><td class="py-1 font-mono break-all">{{ $body ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

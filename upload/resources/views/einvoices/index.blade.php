<x-app-layout title="Hóa đơn điện tử">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Hóa đơn điện tử</h2>
            <div class="flex items-center gap-3">
                @if ($loggedIn)
                    <span class="text-sm text-gray-500">Đang đăng nhập: {{ $company }}</span>
                    <form method="POST" action="{{ route('einvoices.logout') }}">
                        @csrf
                        <button class="text-sm text-gray-600 hover:underline">Thoát cổng thuế</button>
                    </form>
                    <a href="{{ route('einvoices.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-gray-700">Tải hóa đơn</a>
                @else
                    <a href="{{ route('einvoices.login') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-gray-700">Đăng nhập cổng thuế</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-left">
                            <tr>
                                <th class="px-3 py-2">Thời gian tải</th>
                                <th class="px-3 py-2">Loại</th>
                                <th class="px-3 py-2">Kỳ</th>
                                <th class="px-3 py-2">Công ty</th>
                                <th class="px-3 py-2 text-right">Hóa đơn</th>
                                <th class="px-3 py-2 text-right">Đã tải</th>
                                <th class="px-3 py-2 text-right">Lỗi</th>
                                <th class="px-3 py-2">Trạng thái</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($batches as $batch)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $batch->created_at->format('d/m/Y H:i') }}<div class="text-xs text-gray-500">{{ $batch->user?->name }}</div></td>
                                    <td class="px-3 py-2">{{ $batch->directionLabel() }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $batch->from_date->format('d/m/Y') }} - {{ $batch->to_date->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2">{{ $batch->company_name }}</td>
                                    <td class="px-3 py-2 text-right">{{ $batch->total }}</td>
                                    <td class="px-3 py-2 text-right">{{ $batch->downloaded }}</td>
                                    <td class="px-3 py-2 text-right {{ $batch->failed ? 'text-red-600' : '' }}">{{ $batch->failed }}</td>
                                    <td class="px-3 py-2">{{ $batch->statusLabel() }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <a href="{{ route('einvoices.show', $batch) }}" class="text-indigo-700 hover:underline">Xem</a>
                                        @if ($batch->downloaded)
                                            · <a href="{{ route('einvoices.download', $batch) }}" class="text-indigo-700 hover:underline">Tải zip</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-3 py-6 text-center text-gray-500">Chưa có lần tải nào.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">{{ $batches->links() }}</div>
        </div>
    </div>
</x-app-layout>

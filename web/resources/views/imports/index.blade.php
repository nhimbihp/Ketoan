<x-app-layout title="Lịch sử">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lịch sử thống kê</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-left">
                        <tr>
                            <th class="px-4 py-2">Thời gian</th>
                            <th class="px-4 py-2">Loại</th>
                            <th class="px-4 py-2">File</th>
                            <th class="px-4 py-2">Người lập</th>
                            <th class="px-4 py-2 text-right">Số phiếu</th>
                            <th class="px-4 py-2 text-right">Tổng tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($imports as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 whitespace-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">{{ isset($types[$item->document_type]) ? $types[$item->document_type]->label() : $item->document_type }}</td>
                                <td class="px-4 py-2"><a href="{{ route('imports.show', $item) }}" class="text-indigo-700 hover:underline">{{ $item->original_filename }}</a></td>
                                <td class="px-4 py-2">{{ $item->user?->name }}</td>
                                <td class="px-4 py-2 text-right">{{ $item->vouchers_count }}</td>
                                <td class="px-4 py-2 text-right">{{ \App\Support\Money::dot($item->vouchers_sum_total ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Chưa có dữ liệu. <a href="{{ route('imports.create') }}" class="text-indigo-700 hover:underline">Lập phiếu</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $imports->links() }}</div>
        </div>
    </div>
</x-app-layout>

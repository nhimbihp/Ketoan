<x-app-layout title="Khách hàng">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Khách hàng</h2>
            <a href="{{ route('admin.tenants.create') }}" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">+ Thêm khách hàng</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-left">
                        <tr>
                            <th class="px-4 py-2">Tên công ty</th>
                            <th class="px-4 py-2">MST</th>
                            <th class="px-4 py-2">Trạng thái</th>
                            <th class="px-4 py-2">Hết hạn</th>
                            <th class="px-4 py-2 text-right">Người dùng</th>
                            <th class="px-4 py-2 text-right">Lần thống kê</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($tenants as $t)
                            <tr>
                                <td class="px-4 py-2 font-medium">{{ $t->name }}<div class="text-xs text-gray-500">{{ $t->address }}</div></td>
                                <td class="px-4 py-2">{{ $t->tax_code }}</td>
                                <td class="px-4 py-2">
                                    @if ($t->isActive())
                                        <span class="px-2 py-0.5 rounded bg-green-100 text-green-800 text-xs">Hoạt động</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-red-100 text-red-800 text-xs">{{ $t->status === 'suspended' ? 'Đã khoá' : 'Hết hạn' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $t->expires_at?->format('d/m/Y') ?? 'Không giới hạn' }}</td>
                                <td class="px-4 py-2 text-right">{{ $t->users_count }}</td>
                                <td class="px-4 py-2 text-right">{{ $t->imports_count }}</td>
                                <td class="px-4 py-2 text-right"><a href="{{ route('admin.tenants.edit', $t) }}" class="text-indigo-700 hover:underline">Sửa</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Chưa có khách hàng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $tenants->links() }}</div>
        </div>
    </div>
</x-app-layout>

<x-app-layout :title="$type->label()">
    <x-slot name="header">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $type->label() }} <span class="text-gray-400 font-normal">· {{ $import->original_filename }}</span>
            </h2>
            <a href="{{ route('imports.create') }}" class="text-sm text-indigo-700 hover:underline">← Lập phiếu khác</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-3">
            {{-- Bảng phiếu đã thống kê --}}
            <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b flex justify-between text-sm">
                    <span><b>{{ $import->vouchers->count() }}</b> phiếu · {{ $import->row_count }} dòng Excel · bảng mã {{ strtoupper($import->encoding) }}</span>
                    <span>Tổng: <b>{{ \App\Support\Money::dot($import->vouchers->sum('total')) }}</b> đ</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-left">
                            <tr>
                                <th class="px-3 py-2">Trang</th>
                                <th class="px-3 py-2">Số phiếu</th>
                                <th class="px-3 py-2">Ngày</th>
                                <th class="px-3 py-2">Họ tên</th>
                                <th class="px-3 py-2">Nội dung</th>
                                <th class="px-3 py-2">TK</th>
                                <th class="px-3 py-2 text-right">Số tiền</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($import->vouchers as $v)
                                <tr class="align-top">
                                    <td class="px-3 py-2 text-gray-500">{{ $v->page_no }}</td>
                                    <td class="px-3 py-2 font-medium whitespace-nowrap">{{ $v->number }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $v->date?->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2">{{ $v->person }}<div class="text-xs text-gray-500">{{ $v->department }}</div></td>
                                    <td class="px-3 py-2">{{ $v->reason }}</td>
                                    <td class="px-3 py-2 text-xs font-mono whitespace-nowrap">
                                        @foreach ($v->lines as $line)
                                            <div class="{{ $line->role === 'counter' ? 'font-semibold' : 'text-gray-600' }}">
                                                {{ $line->account }} <span class="text-gray-400">{{ \App\Support\Money::dot($line->amount) }}</span>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="px-3 py-2 text-right font-medium whitespace-nowrap">{{ \App\Support\Money::dot($v->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tuỳ chọn in --}}
            <form method="POST" action="{{ route('imports.print.prepare', $import) }}" target="_blank"
                  x-data="{ range: '{{ old('range', 'all') }}' }"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4 self-start lg:sticky lg:top-4">
                @csrf
                <h3 class="font-semibold text-gray-900">In phiếu (khổ A5 ngang)</h3>

                <div>
                    <x-input-label for="company_name" value="Công ty" />
                    <x-text-input id="company_name" name="company_name" class="mt-1 block w-full" :value="old('company_name', $import->company_name)" />
                </div>
                <div>
                    <x-input-label for="company_address" value="Địa chỉ" />
                    <x-text-input id="company_address" name="company_address" class="mt-1 block w-full" :value="old('company_address', $import->company_address)" />
                </div>
                <div>
                    <x-input-label for="decision_id" value="Quyết định / Mẫu số" />
                    <select id="decision_id" name="decision_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">— Không in —</option>
                        @foreach ($decisions as $d)
                            <option value="{{ $d->id }}" @selected($selectedDecision == $d->id)>{{ $d->name }} ({{ $d->form_code }})</option>
                        @endforeach
                    </select>
                </div>

                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium text-gray-700 bg-yellow-200 px-1">Chọn in</legend>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="range" value="all" x-model="range" class="text-indigo-600"> Tất cả ({{ $import->vouchers->count() }} trang)</label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="range" value="pages" x-model="range" class="text-indigo-600"> Từ trang
                        <input type="number" name="from" min="1" max="{{ $import->vouchers->count() }}" value="{{ old('from', 1) }}" @focus="range = 'pages'"
                               class="w-20 border-gray-300 rounded-md text-sm py-1">
                        đến
                        <input type="number" name="to" min="1" max="{{ $import->vouchers->count() }}" value="{{ old('to', $import->vouchers->count()) }}" @focus="range = 'pages'"
                               class="w-20 border-gray-300 rounded-md text-sm py-1">
                    </label>
                </fieldset>

                <div class="grid grid-cols-3 gap-2 pt-2">
                    <button name="action" value="preview" class="px-3 py-2 rounded-md border border-gray-300 text-sm font-medium hover:bg-gray-50">Xem trước</button>
                    <button name="action" value="print" class="px-3 py-2 rounded-md bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">In</button>
                    <button name="action" value="pdf" class="px-3 py-2 rounded-md border border-gray-300 text-sm font-medium hover:bg-gray-50">Tải PDF</button>
                </div>
                <p class="text-xs text-gray-500">Khi in từ trình duyệt: chọn khổ giấy A5, hướng ngang, lề "Không", tỉ lệ 100%. Bản PDF đã đúng khổ.</p>
            </form>

            <div class="lg:col-span-3">
                <form method="POST" action="{{ route('imports.destroy', $import) }}" onsubmit="return confirm('Xoá lần thống kê này?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-600 hover:underline">Xoá lần thống kê này</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

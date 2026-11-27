<x-app-layout title="Tải hóa đơn điện tử">
    <x-slot name="header">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tải hóa đơn điện tử <span class="text-gray-400 font-normal">· {{ $company }}</span>
            </h2>
            <a href="{{ route('einvoices.index') }}" class="text-sm text-indigo-700 hover:underline">← Lịch sử tải</a>
        </div>
    </x-slot>

    @php($results = $processResults)
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('einvoices.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6"
                  x-data="{
                      direction: '{{ old('direction', 'purchase') }}',
                      results: @js($results),
                      result: '{{ old('process_result', '5') }}',
                      changeDirection() { if (!(this.result in this.results[this.direction])) this.result = '5'; }
                  }">
                @csrf

                <div>
                    <x-input-label value="Loại hóa đơn" />
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ($directions as $key => $label)
                            <label class="flex items-center gap-2 px-4 py-2 border rounded-md cursor-pointer has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                <input type="radio" name="direction" value="{{ $key }}" x-model="direction" @change="changeDirection()" class="text-indigo-600">
                                <span class="font-medium">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('direction')" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="from_date" value="Từ ngày" />
                        <x-text-input id="from_date" name="from_date" type="date" class="mt-1 block w-full" required
                                      :value="old('from_date', now()->startOfMonth()->format('Y-m-d'))" />
                        <x-input-error :messages="$errors->get('from_date')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="to_date" value="Đến ngày" />
                        <x-text-input id="to_date" name="to_date" type="date" class="mt-1 block w-full" required
                                      :value="old('to_date', now()->format('Y-m-d'))" />
                        <x-input-error :messages="$errors->get('to_date')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="invoice_status" value="Trạng thái hóa đơn" />
                    <select id="invoice_status" name="invoice_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($invoiceStatuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('invoice_status', '1') === (string) $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="process_result" value="Kết quả kiểm tra" />
                    <select id="process_result" name="process_result" x-model="result"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <template x-for="(label, key) in results[direction]" :key="key">
                            <option :value="key" x-text="label" :selected="key === result"></option>
                        </template>
                    </select>
                    <x-input-error :messages="$errors->get('process_result')" class="mt-2" />
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <x-primary-button>Tải hóa đơn</x-primary-button>
                    <span class="text-sm text-gray-500">Mỗi hóa đơn mất khoảng 1,5 giây, giữ nguyên trang trong khi tải.</span>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<x-app-layout title="Tải hóa đơn điện tử">
    <x-slot name="header">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Hóa đơn {{ mb_strtolower($batch->directionLabel()) }}
                <span class="text-gray-400 font-normal">· {{ $batch->from_date->format('d/m/Y') }} - {{ $batch->to_date->format('d/m/Y') }} · {{ $batch->company_name }}</span>
            </h2>
            <a href="{{ route('einvoices.index') }}" class="text-sm text-indigo-700 hover:underline">← Lịch sử tải</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="einvoiceRunner({
                 status: @js($batch->status),
                 statusLabel: @js($batch->statusLabel()),
                 percent: {{ $batch->percent() }},
                 total: {{ $batch->total }},
                 downloaded: {{ $batch->downloaded }},
                 failed: {{ $batch->failed }},
                 periodIndex: {{ $batch->period_index }},
                 periodCount: {{ count($batch->periods) }},
                 loggedIn: @js($loggedIn),
                 listUrl: @js(route('einvoices.list', $batch)),
                 fetchUrl: @js(route('einvoices.fetch', $batch)),
             })" x-init="autoStart()">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex justify-between text-sm">
                    <span x-text="statusLabel" class="font-medium"></span>
                    <span x-text="percent + '%'"></span>
                </div>
                <div class="w-full bg-gray-200 rounded h-3 overflow-hidden">
                    <div class="bg-indigo-600 h-3 transition-all" :style="'width:' + percent + '%'"></div>
                </div>
                <div class="text-sm text-gray-600" x-show="status === 'listing'">
                    Đang lấy danh sách tháng <span x-text="Math.min(periodIndex + 1, periodCount)"></span>/<span x-text="periodCount"></span>
                </div>
                <div class="text-sm text-gray-600">
                    Tổng <b x-text="total"></b> hóa đơn · đã tải <b x-text="downloaded"></b> · lỗi <b x-text="failed" :class="failed ? 'text-red-600' : ''"></b>
                </div>

                <div x-show="message" class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800" x-text="message"></div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" x-show="!running && status !== 'done' && loggedIn" @click="run()"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-gray-700">
                        Tải tiếp
                    </button>
                    <button type="button" x-show="running" @click="stopRequested = true"
                            class="inline-flex items-center px-4 py-2 border rounded-md text-xs font-semibold uppercase tracking-widest text-gray-700 hover:bg-gray-50">
                        Dừng
                    </button>
                    <a x-show="!loggedIn && status !== 'done'" href="{{ route('einvoices.login') }}" class="text-sm text-indigo-700 hover:underline">Đăng nhập cổng thuế để tải tiếp</a>
                    <a x-show="downloaded > 0 && !running" href="{{ route('einvoices.download', $batch) }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-indigo-500">
                        Tải file zip XML
                    </a>
                    <span x-show="running" class="text-sm text-gray-500">Đang tải, giữ nguyên trang này...</span>
                </div>
            </div>

            @if ($batch->errors)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm">
                    <h3 class="font-semibold text-gray-900 mb-2">Lỗi trong quá trình tải ({{ count($batch->errors) }})</h3>
                    <ul class="list-disc ps-5 space-y-1 text-gray-700 max-h-80 overflow-y-auto">
                        @foreach ($batch->errors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('einvoices.destroy', $batch) }}" onsubmit="return confirm('Xoá lần tải này và các file XML?')">
                @csrf
                @method('DELETE')
                <button class="text-sm text-red-600 hover:underline">Xoá lần tải này</button>
            </form>
        </div>
    </div>

    <script>
        function einvoiceRunner(initial) {
            return {
                ...initial,
                running: false,
                stopRequested: false,
                message: '',
                autoStart() {
                    if (this.loggedIn && (this.status === 'listing' || this.status === 'downloading')) this.run();
                },
                async run() {
                    this.running = true;
                    this.stopRequested = false;
                    this.message = '';
                    const wasActive = this.status !== 'done';
                    while (!this.stopRequested && this.status !== 'done') {
                        const url = this.status === 'listing' ? this.listUrl : this.fetchUrl;
                        let data;
                        try {
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json',
                                },
                            });
                            data = await response.json();
                            this.apply(data);
                            if (!response.ok) {
                                this.message = data.message || ('Lỗi HTTP ' + response.status);
                                if (response.status === 401) this.loggedIn = false;
                                break;
                            }
                        } catch (e) {
                            this.message = 'Mất kết nối tới web, bấm Tải tiếp để chạy tiếp. (' + e.message + ')';
                            break;
                        }
                        if (this.status === 'paused') {
                            this.message = (data.errors || []).slice(-1)[0] || 'Tạm dừng.';
                            break;
                        }
                    }
                    this.running = false;
                    // Reload once finished to show the error list from the server
                    if (wasActive && this.status === 'done') window.location.reload();
                },
                apply(data) {
                    this.status = data.status;
                    this.statusLabel = data.status_label;
                    this.percent = data.percent;
                    this.total = data.total;
                    this.downloaded = data.downloaded;
                    this.failed = data.failed;
                    this.periodIndex = data.period_index;
                    this.periodCount = data.period_count;
                },
            };
        }
    </script>
</x-app-layout>

<x-app-layout title="Lập phiếu">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lập phiếu từ file Excel</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-3">
            <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data"
                  class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf

                <div>
                    <x-input-label for="file" value="File dữ liệu Excel (.xls, .xlsx)" />
                    <input id="file" name="file" type="file" accept=".xls,.xlsx" required
                           class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md">
                    <x-input-error :messages="$errors->get('file')" class="mt-2" />
                </div>

                <div>
                    <x-input-label value="Loại chứng từ" />
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ($types as $key => $type)
                            <label class="flex items-center gap-2 px-4 py-2 border rounded-md cursor-pointer has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                                <input type="radio" name="document_type" value="{{ $key }}" class="text-indigo-600"
                                       @checked(old('document_type', array_key_first($types)) === $key)>
                                <span class="font-medium">{{ $type->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('document_type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="encoding" value="Bảng mã chữ trong file" />
                    <select id="encoding" name="encoding" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="auto" @selected(old('encoding') === 'auto')>Tự nhận diện</option>
                        <option value="tcvn3" @selected(old('encoding') === 'tcvn3')>TCVN3 (ABC – font .VnTime, .VnArial)</option>
                        <option value="unicode" @selected(old('encoding') === 'unicode')>Unicode</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">File cũ gõ bằng font .Vn* sẽ được tự chuyển sang Unicode.</p>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>Thống kê</x-primary-button>
                    <span class="text-sm text-gray-500">Gộp các dòng cùng số phiếu thành 1 phiếu để in.</span>
                </div>
            </form>

            <aside class="space-y-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-700">
                    <h3 class="font-semibold text-gray-900 mb-2">Cấu trúc file Excel</h3>
                    <p class="mb-2">Sheet đầu tiên, dòng 1 là tiêu đề:</p>
                    <table class="w-full text-xs">
                        @foreach (reset($types)->columns() as $col)
                            <tr class="border-t"><td class="py-1 pr-2 font-mono text-gray-500">{{ $col['col'] }}</td><td class="py-1">{{ $col['label'] }}</td></tr>
                        @endforeach
                    </table>
                </div>

                @if ($recent->isNotEmpty())
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm">
                        <h3 class="font-semibold text-gray-900 mb-2">Gần đây</h3>
                        <ul class="space-y-2">
                            @foreach ($recent as $item)
                                <li>
                                    <a href="{{ route('imports.show', $item) }}" class="text-indigo-700 hover:underline">{{ $item->original_filename }}</a>
                                    <div class="text-xs text-gray-500">{{ $item->type()->label() }} · {{ $item->vouchers_count }} phiếu · {{ $item->created_at->format('d/m/Y H:i') }}</div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>

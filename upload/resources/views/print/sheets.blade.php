<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $type->label() }} - {{ $import->original_filename }}</title>
    @include('print.css')
</head>
<body>
<div class="toolbar">
    <a class="ghost" href="{{ route('imports.show', $import) }}">← Quay lại</a>
    <button type="button" onclick="window.print()">In</button>
    <a class="ghost" href="{{ route('imports.pdf', [$import] + array_filter(['from' => $range[0], 'to' => $range[1]])) }}">Tải PDF</a>
    <span class="info">{{ $type->label() }} · {{ $pages->count() }} trang · Khổ A5 ngang — khi in chọn lề "Không", tỉ lệ 100%</span>
</div>

@forelse ($pages as $page)
    <section class="sheet">
        @include($view, $page)
    </section>
@empty
    <p style="text-align:center;margin-top:40px">Không có phiếu nào trong khoảng trang đã chọn.</p>
@endforelse

@if ($autoPrint && $pages->isNotEmpty())
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
@endif
</body>
</html>

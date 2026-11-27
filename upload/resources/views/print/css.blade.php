{{-- CSS dùng chung cho trang in trình duyệt và mPDF. $pdf = true khi xuất PDF. --}}
@if (empty($pdf))<style>@endif
body { font-family: {!! empty($pdf) ? 'Arial, "Liberation Sans", Helvetica, sans-serif' : 'freesans' !!}; font-size: 10pt; color: #000; margin: 0; }
.a { position: absolute; line-height: 1.2; }
.nw { white-space: nowrap; overflow: hidden; }
.c { text-align: center; }
.r { text-align: right; }
.b { font-weight: bold; }
.i { font-style: italic; }
.s8 { font-size: 8pt; }
.s9 { font-size: 9pt; }
.dots { height: 3.4mm; border-bottom: 0.3mm dotted #000; font-size: 1pt; }
.title { font-size: 20pt; font-weight: bold; letter-spacing: 0.5pt; }
@if (empty($pdf))
@page { size: A5 landscape; margin: 0; }
.sheet { position: relative; width: 210mm; height: 148mm; overflow: hidden; background: #fff; box-sizing: border-box; }
@media screen {
    body { background: #d1d5db; padding-top: 56px; }
    .sheet { margin: 8mm auto; box-shadow: 0 2px 10px rgba(0,0,0,.25); }
    .toolbar { position: fixed; top: 0; left: 0; right: 0; height: 48px; background: #1e1b4b; color: #fff; display: flex; align-items: center; gap: 12px; padding: 0 16px; font: 14px system-ui, sans-serif; z-index: 10; }
    .toolbar a, .toolbar button { color: #fff; background: #4338ca; border: 0; border-radius: 6px; padding: 7px 14px; text-decoration: none; cursor: pointer; font: inherit; }
    .toolbar .ghost { background: transparent; border: 1px solid #6366f1; }
    .toolbar .info { margin-left: auto; opacity: .8; }
}
@media print {
    body { background: none; padding: 0; }
    .toolbar { display: none; }
    .sheet { margin: 0; box-shadow: none; page-break-after: always; break-after: page; }
    .sheet:last-child { page-break-after: auto; break-after: auto; }
}
</style>
@endif

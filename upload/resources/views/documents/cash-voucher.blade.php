{{--
    Phiếu thu / Phiếu chi khổ A5 ngang (210 × 148 mm).
    Toạ độ chuyển từ lblphieuthu.lbx / lblphieuchi.lbx (đơn vị 1/10000 inch × 0,00254 = mm).
    Dùng chung cho trình duyệt (.sheet position:relative) và mPDF (toạ độ tính từ mép trang, lề 0).
--}}
@php
    $v = $voucher;
    $lineTop = 36.8;
    $lineGap = count($v->lines) > 7 ? 32.0 / count($v->lines) : 4.6;
    $signers = $signers ?? [];
    $signCols = [
        ['Giám đốc', '(Ký, họ tên, đóng dấu)', 29.4, $signers['director'] ?? null],
        ['Kế toán trưởng', '(Ký, họ tên)', 73.3, $signers['chief_accountant'] ?? null],
        ['Thủ quỹ', '(Ký, họ tên)', 108.9, $signers['cashier'] ?? null],
        ['Người lập', '(Ký, họ tên)', 143.1, $signers['preparer'] ?? null],
        [$labels['person_sign'], '(Ký, họ tên)', 178.1, null],
    ];
    $rows = [
        [47.6, 'Họ và tên', $v->person, 4.6],
        [52.9, 'Địa chỉ', $v->department, 4.6],
        [57.9, $labels['reason'], $v->reason, 8.6],
        [66.9, 'Số tiền', $total.' đồng', 4.6],
    ];
    $wordsLine = $words.' chẵn /.';
@endphp

{{-- Đơn vị --}}
<div class="a nw" style="top:12.2mm; left:12.2mm; width:22mm;">Tên công ty:</div>
<div class="a nw b" style="top:12.2mm; left:34.9mm; width:90mm;">{{ $company }}</div>
<div class="a nw" style="top:18.3mm; left:12.2mm; width:22mm;">Địa chỉ:</div>
<div class="a nw" style="top:18.3mm; left:34.9mm; width:90mm;">{{ $address }}</div>

{{-- Mẫu số / Quyết định --}}
@if ($decision)
    <div class="a nw c s8 b" style="top:12.9mm; left:128.6mm; width:68mm;">{{ $decision->form_code }}</div>
    <div class="a nw c s8 i" style="top:17.4mm; left:128.6mm; width:68mm;">({{ $decision->decision_text }}</div>
    <div class="a nw c s8 i" style="top:21.4mm; left:128.6mm; width:68mm;">{{ $decision->date_text }})</div>
@endif

{{-- Tiêu đề, số, ngày --}}
<div class="a nw c title" style="top:25.5mm; left:47.6mm; width:80mm;">{{ $labels['title'] }}</div>
<div class="a nw b" style="top:31.2mm; left:111.6mm; width:6.5mm;">Số:</div>
<div class="a nw b" style="top:31.2mm; left:118mm; width:30mm;">{{ $v->number }}</div>
<div class="a nw c i" style="top:36.5mm; left:47.6mm; width:80mm;">
    @if ($v->date)
        Ngày {{ $v->date->format('d') }} tháng {{ $v->date->format('m') }} năm {{ $v->date->format('Y') }}
    @else
        Ngày ..... tháng ..... năm ..........
    @endif
</div>

{{-- Tài khoản Nợ / Có --}}
@foreach ($v->lines as $i => $line)
    @php($top = $lineTop + $i * $lineGap)
    @if ($i === 0 || $i === 1)
        <div class="a nw" style="top:{{ $top }}mm; left:124.4mm; width:12mm;">{{ $i === 0 ? $labels['counter_account'] : $labels['detail_account'] }}:</div>
    @endif
    <div class="a nw r" style="top:{{ $top }}mm; left:136.5mm; width:12mm;">{{ $line->account }}</div>
    <div class="a nw r" style="top:{{ $top }}mm; left:149.2mm; width:31.5mm;">{{ \App\Support\Money::dot($line->amount) }}</div>
@endforeach

{{-- Nội dung --}}
@foreach ($rows as [$top, $label, $value, $height])
    <div class="a nw" style="top:{{ $top }}mm; left:12.7mm; width:27mm;">{{ $label }}</div>
    <div class="a nw" style="top:{{ $top }}mm; left:39.7mm; width:2mm;">:</div>
    <div class="a {{ $height > 5 ? '' : 'nw' }}" style="top:{{ $top }}mm; left:41.3mm; width:80mm; height:{{ $height }}mm; overflow:hidden;">{{ $value }}</div>
@endforeach
<div class="a nw" style="top:72.2mm; left:12.7mm; width:27mm;">Bằng chữ</div>
<div class="a nw" style="top:72.2mm; left:39.7mm; width:2mm;">:</div>
<div class="a nw i {{ mb_strlen($wordsLine) > 85 ? 's9' : '' }}" style="top:72.2mm; left:41.3mm; width:156mm;">{{ $wordsLine }}</div>
<div class="a nw" style="top:77.3mm; left:12.7mm; width:27mm;">Kèm theo</div>
<div class="a nw" style="top:77.3mm; left:39.7mm; width:2mm;">:</div>
<div class="a nw" style="top:77.3mm; left:41.3mm; width:100mm;">Chứng từ gốc</div>

{{-- Chữ ký --}}
<div class="a nw c i" style="top:83.3mm; left:130mm; width:70mm;">Ngày ..... tháng ..... năm 20.....</div>
@foreach ($signCols as [$title, $hint, $center, $name])
    <div class="a nw c b" style="top:88.6mm; left:{{ $center - 20 }}mm; width:40mm;">{{ $title }}</div>
    <div class="a nw c i s9" style="top:93.4mm; left:{{ $center - 20 }}mm; width:40mm;">{{ $hint }}</div>
    @if ($name)
        <div class="a nw c b" style="top:116mm; left:{{ $center - 20 }}mm; width:40mm;">{{ $name }}</div>
    @endif
@endforeach

{{-- Phần ghi tay (dòng chấm bằng border để trình duyệt và PDF giống nhau) --}}
@foreach ([[125.9, 'Đã nhận đủ số tiền (viết bằng chữ):', 70], [130.7, '+ Tỷ giá ngoại tệ (vàng, bạc, đá quý):', 72], [135.7, '+ Số tiền quy đổi:', 44]] as [$top, $text, $dotsFrom])
    <div class="a nw" style="top:{{ $top }}mm; left:12.7mm; width:{{ $dotsFrom - 13.5 }}mm;">{{ $text }}</div>
    <div class="a dots" style="top:{{ $top }}mm; left:{{ $dotsFrom }}mm; width:{{ 197 - $dotsFrom }}mm;">&nbsp;</div>
@endforeach

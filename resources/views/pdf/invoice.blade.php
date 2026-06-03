<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<style>
    * { font-family: 'xbriyaz', sans-serif; }
    body { color:#1a1a1a; font-size:12px; line-height:1.6; }
    .header { width:100%; border-bottom:3px solid #0F7A3D; padding-bottom:12px; margin-bottom:20px; }
    .header td { vertical-align:middle; }
    .logo-box { width:78px; height:78px; background:#0F7A3D; color:#fff; text-align:center; line-height:78px; font-size:30px; font-weight:bold; border-radius:10px; }
    .company-name { font-size:19px; font-weight:bold; color:#0F7A3D; }
    .muted { color:#888; font-size:10.5px; }
    .doc-title { font-size:24px; font-weight:bold; color:#045B28; margin-bottom:4px; }

    .info-table { width:100%; margin-bottom:20px; border-spacing:0; }
    .info-cell { width:48%; vertical-align:top; }
    .box { background:#f2f8f4; border:1px solid #d8ebdf; border-radius:8px; padding:14px; }
    .box-title { font-weight:bold; margin-bottom:10px; color:#0F7A3D; font-size:13px; border-bottom:1px solid #d8ebdf; padding-bottom:5px; }
    .cust-name { font-size:15px; font-weight:bold; color:#1a1a1a; margin-bottom:8px; }

    .badge { display:inline-block; padding:2px 10px; border-radius:12px; font-size:11px; font-weight:bold; }
    .badge-paid { background:#e2f5ea; color:#0F7A3D; }
    .badge-unpaid { background:#fde8e8; color:#c0392b; }

    table.items { width:100%; border-collapse:collapse; margin-bottom:18px; }
    table.items th { background:#0F7A3D; color:#fff; padding:10px 8px; font-size:12px; text-align:right; }
    table.items td { padding:9px 8px; border-bottom:1px solid #eee; font-size:11px; text-align:right; vertical-align:middle; }
    table.items tr:nth-child(even) td { background:#f7fbf8; }
    .prod-img { width:38px; height:38px; object-fit:cover; border-radius:5px; border:1px solid #e0e0e0; }
    .prod-name { font-weight:bold; }

    .totals-wrap { width:100%; }
    .totals { width:48%; float:left; border:1px solid #d8ebdf; border-radius:8px; overflow:hidden; }
    .totals td { padding:7px 12px; font-size:12px; }
    .totals tr:nth-child(even) td { background:#f7fbf8; }
    .totals .grand td { font-size:15px; font-weight:bold; color:#fff; background:#0F7A3D; }

    .notes { clear:both; background:#fffdf5; border:1px solid #f0e3b9; border-radius:8px; padding:12px; margin-top:18px; font-size:11px; }
    .notes-title { font-weight:bold; color:#856404; margin-bottom:4px; }
    .footer { text-align:center; color:#aaa; font-size:10px; margin-top:30px; border-top:1px solid #eee; padding-top:10px; }
</style>
</head>
<body>

@php
    $money = fn($h) => number_format($h/100, 2) . ' ر.س';
    $statusMap = ['draft'=>'مسودة','pending'=>'قيد الانتظار','processing'=>'قيد المعالجة','on_hold'=>'معلّق','confirmed'=>'مؤكد','shipped'=>'تم الشحن','completed'=>'مكتمل','cancelled'=>'ملغي'];
@endphp

<table class="header">
    <tr>
        <td style="width:58%;">
            <table><tr>
                <td style="width:90px;"><div class="logo-box">B</div></td>
                <td>
                    <div class="company-name">بصمة لخدمات الأقفال</div>
                    <div class="muted">Basma Automotive Locksmith</div>
                    <div class="muted">الرياض، المملكة العربية السعودية</div>
                    <div class="muted">هاتف: 05XXXXXXXX</div>
                    <div class="muted">الرقم الضريبي: 3000000000</div>
                </td>
            </tr></table>
        </td>
        <td style="width:42%; text-align:left;">
            <div class="doc-title">{{ $docTitle }}</div>
            <div class="muted">رقم المستند: <strong style="color:#1a1a1a;">{{ $order->number }}</strong></div>
            <div class="muted">التاريخ: {{ $order->created_at->format('Y-m-d') }}</div>
        </td>
    </tr>
</table>

<table class="info-table">
    <tr>
        <td class="info-cell">
            <div class="box">
                <div class="box-title">بيانات العميل</div>
                <div class="cust-name">{{ $order->customer->name }}</div>
                <table style="width:100%; font-size:12px;">
                    @if($order->customer->code)<tr><td style="color:#999; padding:3px 0; width:40%;">رقم العميل</td><td style="font-weight:bold; padding:3px 0;">{{ $order->customer->code }}</td></tr>@endif
                    @if($order->customer->phone)<tr><td style="color:#999; padding:3px 0;">الجوال</td><td style="font-weight:bold; padding:3px 0;">{{ $order->customer->phone }}</td></tr>@endif
                    @if($order->customer->email && !str_contains($order->customer->email, 'placeholder'))<tr><td style="color:#999; padding:3px 0;">البريد</td><td style="font-weight:bold; padding:3px 0; font-size:11px;">{{ $order->customer->email }}</td></tr>@endif
                    @if($order->customer->vat_number)<tr><td style="color:#999; padding:3px 0;">الرقم الضريبي</td><td style="font-weight:bold; padding:3px 0;">{{ $order->customer->vat_number }}</td></tr>@endif
                </table>
            </div>
        </td>
        <td style="width:4%;"></td>
        <td class="info-cell">
            <div class="box">
                <div class="box-title">حالة المستند</div>
                <table style="width:100%; font-size:12px;">
                    <tr><td style="color:#999; padding:3px 0; width:40%;">النوع</td><td style="font-weight:bold; padding:3px 0;">{{ $order->type === 'proforma' ? 'عرض سعر' : 'فاتورة ضريبية' }}</td></tr>
                    <tr><td style="color:#999; padding:3px 0;">حالة الطلب</td><td style="font-weight:bold; padding:3px 0;">{{ $statusMap[$order->status] ?? $order->status }}</td></tr>
                    <tr><td style="color:#999; padding:3px 0;">حالة الدفع</td><td style="padding:3px 0;">
                        @if($order->payment_status === 'paid')
                            <span class="badge badge-paid">مدفوع</span>
                        @else
                            <span class="badge badge-unpaid">غير مدفوع</span>
                        @endif
                    </td></tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:8%;">الصورة</th>
            <th>المنتج</th>
            <th style="width:14%;">السعر</th>
            <th style="width:9%;">الكمية</th>
            <th style="width:15%;">الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                @if(!empty($images[$item->id]))
                    <img src="{{ $images[$item->id] }}" class="prod-img">
                @endif
            </td>
            <td>
                <span class="prod-name">{{ $item->title }}</span>
                @if($item->note)<br><span class="muted">{{ $item->note }}</span>@endif
            </td>
            <td>{{ $money($item->unit_price) }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ $money($item->line_total) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals-wrap">
    <table class="totals">
        <tr><td>المجموع الفرعي</td><td style="text-align:left;">{{ $money($order->subtotal) }}</td></tr>
        @if($order->discount_amount > 0)
        <tr><td>الخصم</td><td style="text-align:left; color:#c0392b;">- {{ $money($order->discount_amount) }}</td></tr>
        @endif
        @if($order->shipping > 0)
        <tr><td>الشحن</td><td style="text-align:left;">{{ $money($order->shipping) }}</td></tr>
        @endif
        @if($order->service_fees > 0)
        <tr><td>رسوم الخدمة</td><td style="text-align:left;">{{ $money($order->service_fees) }}</td></tr>
        @endif
        <tr><td>ضريبة القيمة المضافة ({{ rtrim(rtrim(number_format($order->vat_rate,2),'0'),'.') }}%)</td><td style="text-align:left;">{{ $money($order->vat_amount) }}</td></tr>
        <tr class="grand"><td>الإجمالي</td><td style="text-align:left;">{{ $money($order->total) }}</td></tr>
    </table>
</div>

@if($order->notes)
<div class="notes">
    <div class="notes-title">ملاحظات</div>
    {{ $order->notes }}
</div>
@endif

<div class="footer">
    شكراً لتعاملكم معنا — بصمة لخدمات الأقفال
</div>

</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $firm['invoice_title'] }} — {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 20px 24px; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        .muted { color: #64748b; font-size: 8px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: bold; }
        .firm-name { font-size: 16px; font-weight: bold; color: #312e81; }
        .firm-tag { font-size: 10px; color: #4f46e5; font-weight: bold; }
        .doc-title { font-size: 18px; font-weight: bold; text-align: center; margin: 14px 0 10px; letter-spacing: 0.08em; color: #1e293b; border: 1px solid #cbd5e1; padding: 8px; background: #f8fafc; }
        .box { border: 1px solid #e2e8f0; padding: 10px; vertical-align: top; }
        .items th { background: #eef2ff; padding: 7px 5px; font-size: 8px; border: 1px solid #cbd5e1; text-transform: uppercase; }
        .items td { padding: 7px 5px; border: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; }
        .grand td { font-size: 12px; font-weight: bold; background: #eef2ff; border-top: 2px solid #4f46e5; }
        .section-title { font-size: 9px; font-weight: bold; color: #4f46e5; margin: 12px 0 6px; text-transform: uppercase; }
        .words-box { background: #f8fafc; border: 1px dashed #94a3b8; padding: 8px; margin-top: 10px; font-style: italic; }
        .bank-box { background: #f0fdf4; border: 1px solid #86efac; padding: 10px; }
        .sign-block { margin-top: 40px; text-align: right; }
        .sign-line { border-top: 1px solid #334155; width: 200px; margin-left: auto; padding-top: 6px; font-weight: bold; }
    </style>
</head>
<body>

{{-- 1. Supplier / business details --}}
<table>
    <tr>
        <td class="box">
            @if($logoFullPath)
            <img src="{{ $logoFullPath }}" alt="Logo" style="max-height: 48px; max-width: 160px; margin-bottom: 6px;">
            @endif
            <div class="firm-name">{{ $firm['name'] }}</div>
            @if($firm['tagline'])<div class="firm-tag">{{ $firm['tagline'] }}</div>@endif
            @if($firm['address'])<div style="margin-top:6px;">{!! nl2br(e($firm['address'])) !!}</div>@endif
            <table style="margin-top:8px; font-size:9px;">
                @if($firm['phone'])<tr><td width="70"><strong>Mob:</strong></td><td>{{ $firm['phone'] }}</td></tr>@endif
                @if($firm['email'])<tr><td><strong>Email:</strong></td><td>{{ $firm['email'] }}</td></tr>@endif
                @if($firm['website'])<tr><td><strong>Web:</strong></td><td>{{ $firm['website'] }}</td></tr>@endif
                @if($firm['gstin'])<tr><td><strong>GSTIN:</strong></td><td>{{ $firm['gstin'] }}</td></tr>@endif
                @if($firm['pan'])<tr><td><strong>PAN:</strong></td><td>{{ $firm['pan'] }}</td></tr>@endif
                @if($firm['state_name'])<tr><td><strong>State:</strong></td><td>{{ $firm['state_name'] }} @if($firm['state_code'])(Code: {{ $firm['state_code'] }})@endif</td></tr>@endif
            </table>
        </td>
    </tr>
</table>

{{-- 2. Invoice heading --}}
<div class="doc-title">{{ $firm['invoice_title'] }}</div>

{{-- 3. Bill To (left) + Invoice details (right) --}}
<table style="margin-bottom: 12px;">
    <tr>
        <td width="50%" class="box">
            <div class="muted" style="margin-bottom:4px;">Bill To</div>
            @if($invoice->client)
            <strong style="font-size:12px;">{{ $invoice->client->name }}</strong><br>
            @if($invoice->client->client_code)<span style="font-size:9px;">Client Code: {{ $invoice->client->client_code }}</span><br>@endif
            @if($invoice->client->gstin)<strong>GSTIN:</strong> {{ $invoice->client->gstin }}<br>@endif
            @if($invoice->client->pan)<strong>PAN:</strong> {{ $invoice->client->pan }}<br>@endif
            @if($invoice->client->primary_contact_phone)<strong>Mob:</strong> {{ $invoice->client->primary_contact_phone }}<br>@endif
            @if($invoice->client->primary_contact_email)<strong>Email:</strong> {{ $invoice->client->primary_contact_email }}<br>@endif
            @if($invoice->client->billing_address){!! nl2br(e($invoice->client->billing_address)) !!}
            @elseif($invoice->client->registered_address){!! nl2br(e($invoice->client->registered_address)) !!}
            @endif
            @else
            <em>Client record not found</em>
            @endif
        </td>
        <td width="50%" class="box">
            <table style="font-size:9px; width:100%;">
                <tr><td width="120" class="muted">Invoice Number</td><td class="text-right"><strong>{{ $invoice->invoice_number }}</strong></td></tr>
                <tr><td class="muted">Invoice Date</td><td class="text-right">{{ $invoice->date->format('d-m-Y') }}</td></tr>
                <tr><td class="muted">Due Date</td><td class="text-right">{{ $invoice->due_date->format('d-m-Y') }}</td></tr>
                @if($invoice->place_of_supply)<tr><td class="muted">Place of Supply</td><td class="text-right">{{ $invoice->place_of_supply }}</td></tr>@endif
                <tr><td class="muted">Reverse Charge</td><td class="text-right">{{ $invoice->reverse_charge ? 'Yes' : 'No' }}</td></tr>
                @if($invoice->reference_number)<tr><td class="muted">PO / Reference</td><td class="text-right">{{ $invoice->reference_number }}</td></tr>@endif
                @if($invoice->work_period)<tr><td class="muted">Work Period</td><td class="text-right">{{ $invoice->work_period }}</td></tr>@endif
                @if($invoice->project_name)<tr><td class="muted">Project</td><td class="text-right">{{ $invoice->project_name }}</td></tr>@endif
                @if($invoice->financial_year)<tr><td class="muted">Financial Year</td><td class="text-right">{{ $invoice->financial_year }}</td></tr>@endif
            </table>
        </td>
    </tr>
</table>

{{-- 5. Service / product table --}}
<div class="section-title">Service / Product Details</div>
<table class="items">
    <thead>
        <tr>
            <th width="4%">Sr</th>
            <th width="34%">Description</th>
            <th width="10%" class="text-center">HSN/SAC</th>
            <th width="7%" class="text-right">Qty</th>
            <th width="11%" class="text-right">Rate (₹)</th>
            @if($firm['show_gst_breakup'])<th width="7%" class="text-center">GST %</th>@endif
            <th width="13%" class="text-right">Amount (₹)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($invoice->items as $index => $item)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $item->description }}</td>
            <td class="text-center">{{ $item->hsn_sac_code ?? '—' }}</td>
            <td class="text-right">{{ $item->quantity + 0 }}</td>
            <td class="text-right">{{ number_format($item->rate, 2) }}</td>
            @if($firm['show_gst_breakup'])<td class="text-center">{{ ($item->gst_rate ?? 0) + 0 }}%</td>@endif
            <td class="text-right">{{ number_format($item->amount, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="{{ $firm['show_gst_breakup'] ? 7 : 6 }}" class="text-center">No line items</td></tr>
        @endforelse
    </tbody>
</table>

{{-- 6. Tax calculation --}}
<table class="totals" style="width: 48%; margin-left: 52%; margin-top: 8px;">
    <tr><td>Taxable Value</td><td class="text-right">₹ {{ number_format($taxableValue, 2) }}</td></tr>
    @if($firm['show_gst_breakup'] && $invoice->cgst > 0)
    <tr><td>CGST</td><td class="text-right">₹ {{ number_format($invoice->cgst, 2) }}</td></tr>
    <tr><td>SGST</td><td class="text-right">₹ {{ number_format($invoice->sgst, 2) }}</td></tr>
    @endif
    @if($firm['show_gst_breakup'] && $invoice->igst > 0)
    <tr><td>IGST</td><td class="text-right">₹ {{ number_format($invoice->igst, 2) }}</td></tr>
    @endif
    @if(!$firm['show_gst_breakup'] || ($invoice->cgst == 0 && $invoice->igst == 0 && $invoice->tax > 0))
    <tr><td>Tax</td><td class="text-right">₹ {{ number_format($invoice->tax, 2) }}</td></tr>
    @endif
    <tr class="grand"><td>Total Invoice Value</td><td class="text-right">₹ {{ number_format($invoice->total_amount, 2) }}</td></tr>
</table>

{{-- 7. Amount in words --}}
<div class="words-box">
    <strong>Amount in Words:</strong> {{ $amountInWords }}
</div>

@if($invoice->notes)
<p style="margin-top:10px;"><strong>Remarks:</strong> {{ $invoice->notes }}</p>
@endif

{{-- 8. Bank details --}}
@if($firm['bank_name'] || $firm['bank_account_number'])
<div class="section-title">Bank Details (for payment)</div>
<div class="bank-box">
    <table style="font-size:9px;">
        @if($firm['bank_name'])<tr><td width="110"><strong>Bank Name</strong></td><td>{{ $firm['bank_name'] }}</td></tr>@endif
        @if($firm['bank_account_name'])<tr><td><strong>Account Name</strong></td><td>{{ $firm['bank_account_name'] }}</td></tr>@endif
        @if($firm['bank_account_number'])<tr><td><strong>Account No.</strong></td><td>{{ $firm['bank_account_number'] }}</td></tr>@endif
        @if($firm['bank_ifsc'])<tr><td><strong>IFSC</strong></td><td>{{ $firm['bank_ifsc'] }}</td></tr>@endif
        @if($firm['bank_upi'])<tr><td><strong>UPI ID</strong></td><td>{{ $firm['bank_upi'] }}</td></tr>@endif
        @if(!empty($paymentUrl))
        <tr><td><strong>Pay (UPI)</strong></td><td style="font-size:8px; word-break:break-all;">{{ $paymentUrl }}</td></tr>
        @endif
    </table>
</div>
@endif

{{-- 9. Terms & 10. Signature --}}
<table style="margin-top: 16px;">
    <tr>
        <td width="58%" style="vertical-align:top; font-size:9px;">
            <strong>Terms &amp; Conditions</strong><br>
            {!! nl2br(e($firm['invoice_terms'])) !!}
            @if($firm['invoice_footer'])<br><br>{{ $firm['invoice_footer'] }}@endif
        </td>
        <td width="42%" style="vertical-align:bottom;">
            <div class="sign-block">
                <div>For <strong>{{ $firm['name'] }}</strong></div>
                @if(!empty($firm['signatory_name']))
                <div style="margin-top:28px; font-size:11px;">{{ $firm['signatory_name'] }}</div>
                @else
                <div style="margin-top:36px;">&nbsp;</div>
                @endif
                <div class="sign-line">Authorized Signatory</div>
            </div>
        </td>
    </tr>
</table>

</body>
</html>

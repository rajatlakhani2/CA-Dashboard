@php
    use App\Models\Setting;
    $firm = [
        'name' => Setting::get('company_name', ''),
        'tagline' => Setting::get('company_tagline', 'Chartered Accountants'),
        'address' => Setting::get('company_address', ''),
        'phone' => Setting::get('company_phone', ''),
        'email' => Setting::get('company_email', ''),
        'gstin' => Setting::get('firm_gstin', ''),
    ];
    // One chronological ledger: invoices are debits, payments are credits, with a running balance.
    $rows = collect();
    foreach ($invoices as $inv) {
        $rows->push(['date' => $inv->date, 'type' => 'Invoice', 'ref' => $inv->invoice_number, 'debit' => (float) $inv->total_amount, 'credit' => 0.0]);
    }
    foreach ($payments as $pay) {
        $rows->push(['date' => $pay->payment_date, 'type' => 'Payment', 'ref' => $pay->receipt_number . ($pay->invoice?->invoice_number ? ' (against ' . $pay->invoice->invoice_number . ')' : ''), 'debit' => 0.0, 'credit' => (float) $pay->amount]);
    }
    $rows = $rows->sortBy(fn ($r) => optional($r['date'])->format('Y-m-d') . ($r['type'] === 'Invoice' ? '0' : '1'))->values();
    $running = 0.0;
    $inr = fn ($v) => '₹ ' . number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statement of Account – {{ $client->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 22px 26px; line-height: 1.45; }
        .head { border-bottom: 2px solid #0c1f4a; padding-bottom: 10px; margin-bottom: 14px; }
        .firm { font-size: 16px; font-weight: bold; color: #0c1f4a; }
        .muted { color: #64748b; }
        h1 { font-size: 14px; margin: 0 0 2px; color: #0c1f4a; letter-spacing: .5px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .ledger th { background: #0c1f4a; color: #fff; font-size: 9px; text-align: left; padding: 6px; }
        .ledger td { padding: 6px; border-bottom: 1px solid #e2e8f0; }
        .r { text-align: right; }
        .totals { margin-top: 14px; width: 45%; margin-left: 55%; }
        .totals td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; }
        .totals .bal td { font-weight: bold; font-size: 12px; border-top: 2px solid #0c1f4a; border-bottom: 0; }
        .foot { margin-top: 26px; font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    <div class="head">
        @if($firm['name'])<div class="firm">{{ $firm['name'] }}</div>@endif
        @if($firm['tagline'])<div class="muted">{{ $firm['tagline'] }}</div>@endif
        <div class="muted">{{ trim(implode(' · ', array_filter([$firm['address'], $firm['phone'], $firm['email'], $firm['gstin'] ? 'GSTIN ' . $firm['gstin'] : null]))) }}</div>
    </div>

    <table class="meta">
        <tr>
            <td style="width:60%">
                <h1>STATEMENT OF ACCOUNT</h1>
                <div><strong>{{ $client->name }}</strong></div>
                @if($client->client_code ?? null)<div class="muted">Client code: {{ $client->client_code }}</div>@endif
                @if($client->gstin ?? null)<div class="muted">GSTIN: {{ $client->gstin }}</div>@endif
            </td>
            <td class="r">
                <div class="muted">Statement date</div>
                <div><strong>{{ now()->format('d M Y') }}</strong></div>
            </td>
        </tr>
    </table>

    <table class="ledger" style="margin-top:12px">
        <thead>
            <tr><th style="width:14%">Date</th><th style="width:12%">Type</th><th>Reference</th><th class="r" style="width:14%">Debit</th><th class="r" style="width:14%">Credit</th><th class="r" style="width:15%">Balance</th></tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            @php $running += $row['debit'] - $row['credit']; @endphp
            <tr>
                <td>{{ optional($row['date'])->format('d M Y') }}</td>
                <td>{{ $row['type'] }}</td>
                <td>{{ $row['ref'] }}</td>
                <td class="r">{{ $row['debit'] ? $inr($row['debit']) : '' }}</td>
                <td class="r">{{ $row['credit'] ? $inr($row['credit']) : '' }}</td>
                <td class="r">{{ $inr($running) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="muted" style="text-align:center;padding:16px">No invoices or payments yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Total invoiced</td><td class="r">{{ $inr($totalInvoiced) }}</td></tr>
        <tr><td>Total received</td><td class="r">{{ $inr($totalPaid) }}</td></tr>
        <tr class="bal"><td>{{ $balance >= 0 ? 'Balance due' : 'Advance / excess received' }}</td><td class="r">{{ $inr(abs($balance)) }}</td></tr>
    </table>

    <p class="foot">This is a computer-generated statement. Please report any difference within 15 days of receiving it.</p>
</body>
</html>

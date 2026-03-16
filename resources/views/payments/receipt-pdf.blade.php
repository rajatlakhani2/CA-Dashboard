<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $payment->receipt_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.6;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
        }

        .receipt-number {
            font-size: 14px;
            color: #888;
            margin-top: 5px;
        }

        .info-section {
            margin: 20px 0;
        }

        .info-row {
            clear: both;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-label {
            float: left;
            width: 40%;
            color: #666;
            font-weight: bold;
            font-size: 13px;
        }

        .info-value {
            float: left;
            width: 60%;
            font-size: 14px;
        }

        .amount-box {
            background: #f8f9fa;
            border: 2px solid #4f46e5;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }

        .amount-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .amount-value {
            font-size: 32px;
            font-weight: bold;
            color: #4f46e5;
        }

        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 11px;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .signature {
            margin-top: 50px;
            float: right;
            text-align: center;
            width: 40%;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="title">Payment Receipt</div>
            <div class="receipt-number">#{{ $payment->receipt_number }}</div>
            <div style="margin-top:5px; font-size:12px;">{{ \App\Models\Setting::get('company_name', 'CA Dashboard Corp') }}</div>
            @php $firmGstin = \App\Models\Setting::get('firm_gstin', ''); @endphp
            @if($firmGstin)
            <div style="font-size:11px; color:#666;">GSTIN: {{ $firmGstin }}</div>
            @endif
        </div>

        <div class="info-section clearfix">
            <div class="info-row clearfix">
                <div class="info-label">Receipt Date</div>
                <div class="info-value">{{ $payment->payment_date->format('d M, Y') }}</div>
            </div>
            <div class="info-row clearfix">
                <div class="info-label">Client</div>
                <div class="info-value">{{ $payment->invoice->client->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row clearfix">
                <div class="info-label">Invoice Number</div>
                <div class="info-value">{{ $payment->invoice->invoice_number }}</div>
            </div>
            <div class="info-row clearfix">
                <div class="info-label">Payment Mode</div>
                <div class="info-value">{{ $payment->payment_mode }}</div>
            </div>
            @if($payment->reference_number)
            <div class="info-row clearfix">
                <div class="info-label">Reference / Txn ID</div>
                <div class="info-value">{{ $payment->reference_number }}</div>
            </div>
            @endif
        </div>

        <div class="amount-box">
            <div class="amount-label">Amount Received</div>
            <div class="amount-value">₹{{ number_format($payment->amount, 2) }}</div>
        </div>

        @if($payment->notes)
        <div style="margin-top:20px; font-size:12px; color:#666;">
            <strong>Notes:</strong> {{ $payment->notes }}
        </div>
        @endif

        <div class="signature clearfix">
            <br><br><br>
            <div style="border-top: 1px solid #ccc; padding-top: 5px;">
                {{ \App\Models\Setting::get('company_name', 'CA Dashboard Corp') }}<br>
                Authorized Signatory
            </div>
        </div>

        <div class="footer" style="clear:both;">
            This is a computer-generated receipt and does not require physical signature.
        </div>
    </div>
</body>

</html>
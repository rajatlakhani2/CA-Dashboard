<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .header {
            width: 100%;
            margin-bottom: 40px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }

        .header-content {
            width: 100%;
        }

        .logo-area {
            float: left;
            width: 50%;
        }

        .company-details {
            float: right;
            width: 50%;
            text-align: right;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .invoice-details {
            margin-top: 30px;
            width: 100%;
            clear: both;
        }

        .col-left {
            float: left;
            width: 50%;
        }

        .col-right {
            float: right;
            width: 50%;
            text-align: right;
        }

        .grey-label {
            color: #888;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .text-lg {
            font-size: 16px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }

        th {
            text-align: left;
            background-color: #f8f9fa;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }

        .text-right {
            text-align: right;
        }

        .totals {
            margin-top: 30px;
            float: right;
            width: 40%;
        }

        .total-row {
            padding: 5px 0;
            width: 100%;
            clear: both;
        }

        .total-label {
            float: left;
            width: 50%;
            color: #666;
        }

        .total-value {
            float: right;
            width: 50%;
            text-align: right;
            font-weight: bold;
        }

        .grand-total {
            border-top: 2px solid #eee;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 18px;
            color: #4f46e5;
        }

        .notes {
            margin-top: 60px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            font-size: 12px;
            color: #666;
            clear: both;
        }

        .status-badge {
            background-color: #eee;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Clearfix */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>

<body>
    <div class="container clearfix">
        <!-- Header -->
        <div class="header clearfix">
            <div class="logo-area">
                <div class="title">INVOICE</div>
                <div>#{{ $invoice->invoice_number }}</div>
                <div style="margin-top:10px;">
                    <span class="status-badge" style="background-color: {{ $invoice->status == 'Paid' ? '#dcfce7; color: #166534' : '#f1f5f9; color: #475569' }}">
                        {{ $invoice->status }}
                    </span>
                </div>
            </div>
            <div class="company-details">
                <div class="text-lg">{{ \App\Models\Setting::get('company_name', 'RLA Dashboard Corp') }}</div>
                <div style="white-space: pre-line;">{{ \App\Models\Setting::get('company_address', "123 Business Street\nTech City, TC 90210") }}</div>
                <div>{{ \App\Models\Setting::get('company_email', 'billing@cadashboard.com') }}</div>
            </div>
        </div>

        <!-- Details -->
        <div class="invoice-details clearfix">
            <div class="col-left">
                <div class="grey-label">Bill To</div>
                <div class="text-lg">{{ $invoice->client->name }}</div>
                <div>{{ $invoice->client->client_code }}</div>
                @if($invoice->client->contact_email)
                <div>{{ $invoice->client->contact_email }}</div>
                @endif
                @if($invoice->client->address)
                <div>{{ $invoice->client->address }}</div>
                @endif
            </div>
            <div class="col-right">
                <div style="margin-bottom: 20px;">
                    <div class="grey-label">Invoice Date</div>
                    <div class="text-lg">{{ $invoice->date->format('d M, Y') }}</div>
                </div>
                <div>
                    <div class="grey-label">Due Date</div>
                    <div class="text-lg">{{ $invoice->due_date->format('d M, Y') }}</div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <table width="100%">
            <thead>
                <tr>
                    <th style="width: 50%;">Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity + 0 }}</td>
                    <td class="text-right">{{ number_format($item->rate, 2) }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals clearfix">
            <div class="total-row">
                <div class="total-label">Subtotal</div>
                <div class="total-value">{{ number_format($invoice->subtotal, 2) }}</div>
            </div>
            <div class="total-row">
                <div class="total-label">Tax</div>
                <div class="total-value">{{ number_format($invoice->tax, 2) }}</div>
            </div>
            <div class="total-row grand-total">
                <div class="total-label" style="color:#4f46e5;">Total</div>
                <div class="total-value" style="color:#4f46e5;">{{ number_format($invoice->total_amount, 2) }}</div>
            </div>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <strong>Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif
    </div>
</body>

</html>
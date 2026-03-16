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
            margin-bottom: 30px;
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
            margin-top: 20px;
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
            margin-top: 30px;
        }

        th {
            text-align: left;
            background-color: #f8f9fa;
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
            padding: 8px 6px;
            border-bottom: 1px solid #ddd;
        }

        td {
            padding: 10px 6px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            margin-top: 20px;
            float: right;
            width: 45%;
        }

        .total-row {
            padding: 4px 0;
            width: 100%;
            clear: both;
        }

        .total-label {
            float: left;
            width: 55%;
            color: #666;
            font-size: 13px;
        }

        .total-value {
            float: right;
            width: 45%;
            text-align: right;
            font-weight: bold;
            font-size: 13px;
        }

        .grand-total {
            border-top: 2px solid #eee;
            padding-top: 8px;
            margin-top: 8px;
            font-size: 16px;
            color: #4f46e5;
        }

        .notes {
            margin-top: 50px;
            border-top: 1px solid #eee;
            padding-top: 15px;
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

        .gst-info {
            margin-top: 10px;
            font-size: 12px;
            color: #555;
        }

        .gst-info-row {
            clear: both;
            padding: 2px 0;
        }

        .gst-info-label {
            float: left;
            width: 30%;
            color: #888;
            font-size: 11px;
        }

        .gst-info-value {
            float: left;
            width: 70%;
            font-size: 12px;
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
                <div class="title">TAX INVOICE</div>
                <div>#{{ $invoice->invoice_number }}</div>
                @if($invoice->financial_year)
                <div style="font-size:11px; color:#888;">FY: {{ $invoice->financial_year }}</div>
                @endif
                <div style="margin-top:10px;">
                    <span class="status-badge" style="background-color: {{ $invoice->status == 'Paid' ? '#dcfce7; color: #166534' : '#f1f5f9; color: #475569' }}">
                        {{ $invoice->status }}
                    </span>
                </div>
                @if($invoice->reverse_charge)
                <div style="margin-top:5px; font-size:11px; color:#dc2626; font-weight:bold;">
                    REVERSE CHARGE APPLICABLE
                </div>
                @endif
            </div>
            <div class="company-details">
                <div class="text-lg">{{ \App\Models\Setting::get('company_name', 'CA Dashboard Corp') }}</div>
                <div style="white-space: pre-line;">{{ \App\Models\Setting::get('company_address', "123 Business Street\nTech City, TC 90210") }}</div>
                <div>{{ \App\Models\Setting::get('company_email', 'billing@cadashboard.com') }}</div>
                @php $firmGstin = \App\Models\Setting::get('firm_gstin', ''); @endphp
                @if($firmGstin)
                <div style="margin-top:5px; font-weight:bold;">GSTIN: {{ $firmGstin }}</div>
                @endif
            </div>
        </div>

        <!-- Details -->
        <div class="invoice-details clearfix">
            <div class="col-left">
                <div class="grey-label">Bill To</div>
                <div class="text-lg">{{ $invoice->client->name }}</div>
                <div>{{ $invoice->client->client_code }}</div>
                @if($invoice->client->gstin)
                <div style="font-weight:bold;">GSTIN: {{ $invoice->client->gstin }}</div>
                @endif
                @if($invoice->client->primary_contact_email)
                <div>{{ $invoice->client->primary_contact_email }}</div>
                @endif
                @if($invoice->client->billing_address)
                <div>{{ $invoice->client->billing_address }}</div>
                @elseif($invoice->client->registered_address)
                <div>{{ $invoice->client->registered_address }}</div>
                @endif
            </div>
            <div class="col-right">
                <div style="margin-bottom: 15px;">
                    <div class="grey-label">Invoice Date</div>
                    <div class="text-lg">{{ $invoice->date->format('d M, Y') }}</div>
                </div>
                <div style="margin-bottom: 15px;">
                    <div class="grey-label">Due Date</div>
                    <div class="text-lg">{{ $invoice->due_date->format('d M, Y') }}</div>
                </div>
                @if($invoice->place_of_supply)
                <div>
                    <div class="grey-label">Place of Supply</div>
                    <div>{{ $invoice->place_of_supply }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Table -->
        <table width="100%">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Description</th>
                    <th class="text-center" style="width: 10%;">HSN/SAC</th>
                    <th class="text-right" style="width: 8%;">Qty</th>
                    <th class="text-right" style="width: 12%;">Rate</th>
                    <th class="text-center" style="width: 10%;">GST %</th>
                    <th class="text-right" style="width: 15%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-center">{{ $item->hsn_sac_code ?? '-' }}</td>
                    <td class="text-right">{{ $item->quantity + 0 }}</td>
                    <td class="text-right">{{ number_format($item->rate, 2) }}</td>
                    <td class="text-center">{{ ($item->gst_rate ?? 0) + 0 }}%</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals clearfix">
            <div class="total-row">
                <div class="total-label">Subtotal</div>
                <div class="total-value">₹{{ number_format($invoice->subtotal, 2) }}</div>
            </div>
            @if($invoice->cgst > 0)
            <div class="total-row">
                <div class="total-label">CGST</div>
                <div class="total-value">₹{{ number_format($invoice->cgst, 2) }}</div>
            </div>
            <div class="total-row">
                <div class="total-label">SGST</div>
                <div class="total-value">₹{{ number_format($invoice->sgst, 2) }}</div>
            </div>
            @endif
            @if($invoice->igst > 0)
            <div class="total-row">
                <div class="total-label">IGST</div>
                <div class="total-value">₹{{ number_format($invoice->igst, 2) }}</div>
            </div>
            @endif
            @if($invoice->cgst == 0 && $invoice->igst == 0 && $invoice->tax > 0)
            <div class="total-row">
                <div class="total-label">Tax</div>
                <div class="total-value">₹{{ number_format($invoice->tax, 2) }}</div>
            </div>
            @endif
            <div class="total-row grand-total">
                <div class="total-label" style="color:#4f46e5;">Total</div>
                <div class="total-value" style="color:#4f46e5;">₹{{ number_format($invoice->total_amount, 2) }}</div>
            </div>
        </div>

        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes">
            <strong>Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif

        <!-- Footer -->
        <div class="notes" style="margin-top: 30px; font-size:11px;">
            <div style="float:left; width:50%;">
                <strong>Terms & Conditions:</strong><br>
                Payment is due within the specified due date.<br>
                Late payments may attract interest as per applicable laws.
            </div>
            <div style="float:right; width:40%; text-align:right;">
                <br><br><br>
                <strong>{{ \App\Models\Setting::get('company_name', 'CA Dashboard Corp') }}</strong><br>
                Authorized Signatory
            </div>
        </div>
    </div>
</body>

</html>
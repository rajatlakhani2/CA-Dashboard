@component('mail::message')
# Payment Received

Dear {{ $payment->invoice->client->name ?? 'Client' }},

We have received your payment. Below are the details:

**Payment Details:**
- **Receipt Number:** {{ $payment->receipt_number }}
- **Amount:** ₹{{ number_format($payment->amount, 2) }}
- **Payment Date:** {{ $payment->payment_date->format('d M, Y') }}
- **Payment Mode:** {{ $payment->payment_mode }}
@if($payment->reference_number)
- **Reference / Txn ID:** {{ $payment->reference_number }}
@endif
- **Against Invoice:** {{ $payment->invoice->invoice_number }}

@php
$balance = $payment->invoice->total_amount - $payment->invoice->payments->sum('amount');
@endphp

@if($balance > 0)
**Outstanding Balance:** ₹{{ number_format($balance, 2) }}
@else
Your invoice is now **fully paid**. Thank you!
@endif

Regards,
{{ \App\Models\Setting::get('company_name', 'CA Dashboard Corp') }}
@endcomponent
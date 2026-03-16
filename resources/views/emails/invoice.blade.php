@component('mail::message')
# Invoice {{ $invoice->invoice_number }}

Dear {{ $invoice->client->name }},

Please find attached the invoice for your reference.

**Invoice Details:**
- **Invoice Number:** {{ $invoice->invoice_number }}
- **Date:** {{ $invoice->date->format('d M, Y') }}
- **Due Date:** {{ $invoice->due_date->format('d M, Y') }}
- **Total Amount:** ₹{{ number_format($invoice->total_amount, 2) }}

@if($invoice->cgst > 0)
- CGST: ₹{{ number_format($invoice->cgst, 2) }}
- SGST: ₹{{ number_format($invoice->sgst, 2) }}
@endif
@if($invoice->igst > 0)
- IGST: ₹{{ number_format($invoice->igst, 2) }}
@endif

Kindly make the payment before the due date.

Thank you for your business.

Regards,
{{ \App\Models\Setting::get('company_name', 'CA Dashboard Corp') }}
@endcomponent
<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Setting;

class InvoicePdfData
{
    public static function for(Invoice $invoice): array
    {
        $invoice->loadMissing(['client', 'items']);

        $firm = [
            'name' => Setting::get('company_name', 'RAJAT LAKHANI & ASSOCIATES'),
            'tagline' => Setting::get('company_tagline', 'Chartered Accountants'),
            'address' => Setting::get('company_address', ''),
            'phone' => Setting::get('company_phone', ''),
            'email' => Setting::get('company_email', ''),
            'website' => Setting::get('company_website', ''),
            'gstin' => Setting::get('firm_gstin', ''),
            'pan' => Setting::get('firm_pan', ''),
            'state_name' => Setting::get('firm_state_name', 'Gujarat'),
            'state_code' => Setting::get('firm_state_code', '24'),
            'logo_path' => Setting::get('company_logo_path', ''),
            'bank_name' => Setting::get('bank_name', ''),
            'bank_account_name' => Setting::get('bank_account_name', ''),
            'bank_account_number' => Setting::get('bank_account_number', ''),
            'bank_ifsc' => Setting::get('bank_ifsc', ''),
            'bank_upi' => Setting::get('bank_upi', ''),
            'invoice_title' => Setting::get('invoice_title', 'TAX INVOICE'),
            'invoice_terms' => Setting::get('invoice_terms', self::defaultTerms()),
            'invoice_footer' => Setting::get('invoice_footer', ''),
            'payment_days' => Setting::get('invoice_payment_days', '15'),
            'jurisdiction' => Setting::get('invoice_jurisdiction', 'Ahmedabad'),
            'show_gst_breakup' => Setting::get('invoice_show_gst_breakup', '1') === '1',
            'signatory_name' => Setting::get('invoice_signatory_name', ''),
        ];

        $logoFullPath = null;
        if ($firm['logo_path'] && file_exists(public_path($firm['logo_path']))) {
            $logoFullPath = public_path($firm['logo_path']);
        }

        $paymentUrl = $invoice->payment_url;
        if (! $paymentUrl && config('payments.upi_enabled')) {
            $paymentUrl = app(\App\Support\InvoicePaymentLinkBuilder::class)->build($invoice);
        }

        return [
            'invoice' => $invoice,
            'firm' => $firm,
            'logoFullPath' => $logoFullPath,
            'amountInWords' => IndianAmountInWords::rupees($invoice->total_amount),
            'taxableValue' => $invoice->subtotal,
            'paymentUrl' => $paymentUrl,
        ];
    }

    public static function defaultTerms(): string
    {
        return implode("\n", [
            '1. Payment is due within the number of days stated on this invoice.',
            '2. Interest @ 18% p.a. may be charged on overdue amounts.',
            '3. Subject to Ahmedabad jurisdiction.',
        ]);
    }

    public static function settingKeys(): array
    {
        return [
            'company_tagline', 'company_phone', 'company_website', 'firm_pan', 'firm_state_name',
            'bank_name', 'bank_account_name', 'bank_account_number', 'bank_ifsc', 'bank_upi',
            'company_logo_path', 'invoice_number_prefix', 'invoice_payment_days', 'invoice_jurisdiction',
        ];
    }
}

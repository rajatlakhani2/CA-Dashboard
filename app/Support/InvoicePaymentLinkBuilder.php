<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Setting;

class InvoicePaymentLinkBuilder
{
    public function build(Invoice $invoice): ?string
    {
        if (! config('payments.upi_enabled')) {
            return null;
        }

        $upi = trim((string) Setting::get('bank_upi', ''));
        if ($upi === '') {
            return null;
        }

        $balance = max(0.01, $invoice->balanceDue());
        if ($balance <= 0) {
            return null;
        }

        $params = [
            'pa' => $upi,
            'pn' => $this->truncate(Setting::get('company_name', 'RLA'), 50),
            'am' => number_format($balance, 2, '.', ''),
            'cu' => 'INR',
            'tn' => $this->truncate($invoice->invoice_number, 50),
        ];

        return 'upi://pay?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function qrImageUrl(?string $paymentUrl, int $size = 160): ?string
    {
        if (! $paymentUrl) {
            return null;
        }

        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . rawurlencode($paymentUrl);
    }

    protected function truncate(string $value, int $max): string
    {
        return mb_substr(trim($value), 0, $max);
    }
}

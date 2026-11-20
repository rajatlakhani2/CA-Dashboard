<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\User;
use App\Services\PushNotificationService;

/** Phone notification to partners and managers when a client payment is recorded. */
class PushPaymentObserver
{
    public function created(Payment $payment): void
    {
        try {
            $push = app(PushNotificationService::class);
            if (! $push->isConfigured()) {
                return;
            }

            $invoice = $payment->invoice;
            $orgId = $payment->organization_id ?? $invoice?->organization_id;
            if (! $orgId) {
                return; // never notify across firms
            }
            $amount = '₹' . number_format((float) $payment->amount, 0);
            $body = implode(' · ', array_filter([
                $invoice?->client?->name,
                $invoice?->invoice_number,
                $payment->payment_mode,
            ]));

            User::withoutGlobalScopes()
                ->where('organization_id', $orgId)
                ->whereIn('role', ['partner', 'manager', 'ceo'])
                ->whereKeyNot(auth()->id() ?? 0)
                ->get()
                ->filter(fn (User $u) => $u->canAccessModule('payments'))
                ->each(fn (User $u) => $push->sendToUser(
                    $u,
                    'payment_received',
                    "Payment received: {$amount}",
                    $body ?: 'A payment was recorded.',
                    route('payments.index'),
                ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

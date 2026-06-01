<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SensitiveActionLogger
{
    public const LOG_NAME = 'sensitive_actions';

    public function log(
        string $event,
        ?Model $subject = null,
        array $properties = [],
        ?string $description = null,
    ): void {
        $logger = activity(self::LOG_NAME)
            ->event($event)
            ->withProperties($properties);

        if ($subject) {
            $logger->performedOn($subject);
        }

        if (auth()->check()) {
            $logger->causedBy(auth()->user());
        }

        $logger->log($description ?? $event);
    }

    public function invoiceDeleted(Invoice $invoice): void
    {
        $invoice->loadMissing('client');

        $this->log(
            'invoice_deleted',
            $invoice,
            [
                'invoice_number' => $invoice->invoice_number,
                'client_id' => $invoice->client_id,
                'client_name' => $invoice->client?->name,
                'total_amount' => (float) $invoice->total_amount,
                'status' => $invoice->status,
            ],
            'deleted invoice ' . $invoice->invoice_number,
        );
    }

    public function invoiceSent(Invoice $invoice, string $channel): void
    {
        $invoice->loadMissing('client');

        $this->log(
            'invoice_sent',
            $invoice,
            [
                'channel' => $channel,
                'invoice_number' => $invoice->invoice_number,
                'client_id' => $invoice->client_id,
                'client_name' => $invoice->client?->name,
                'total_amount' => (float) $invoice->total_amount,
            ],
            "sent invoice {$invoice->invoice_number} via {$channel}",
        );
    }

    public function invoiceUpdated(Invoice $invoice, array $before): void
    {
        $invoice->loadMissing('client');

        $changes = [];
        foreach (['status', 'total_amount', 'client_id', 'invoice_number'] as $key) {
            $after = match ($key) {
                'status' => $invoice->status,
                'total_amount' => (float) $invoice->total_amount,
                'client_id' => (int) $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
                default => null,
            };
            if (($before[$key] ?? null) != $after) {
                $changes[$key] = ['from' => $before[$key] ?? null, 'to' => $after];
            }
        }

        if ($changes === []) {
            return;
        }

        $this->log(
            'invoice_updated',
            $invoice,
            [
                'invoice_number' => $invoice->invoice_number,
                'client_name' => $invoice->client?->name,
                'changes' => $changes,
            ],
            'updated invoice ' . $invoice->invoice_number,
        );
    }

    public function paymentCreated(Payment $payment): void
    {
        $payment->loadMissing('invoice.client');

        $this->log(
            'payment_created',
            $payment,
            [
                'receipt_number' => $payment->receipt_number,
                'amount' => (float) $payment->amount,
                'payment_mode' => $payment->payment_mode,
                'invoice_id' => $payment->invoice_id,
                'invoice_number' => $payment->invoice?->invoice_number,
                'client_name' => $payment->invoice?->client?->name,
            ],
            'recorded payment ' . $payment->receipt_number,
        );
    }

    public function paymentDeleted(Payment $payment): void
    {
        $payment->loadMissing('invoice.client');

        $this->log(
            'payment_deleted',
            $payment,
            [
                'receipt_number' => $payment->receipt_number,
                'amount' => (float) $payment->amount,
                'invoice_id' => $payment->invoice_id,
                'invoice_number' => $payment->invoice?->invoice_number,
                'client_name' => $payment->invoice?->client?->name,
            ],
            'deleted payment receipt ' . $payment->receipt_number,
        );
    }

    public function clientDeleted(Client $client): void
    {
        $this->log(
            'client_deleted',
            $client,
            [
                'client_code' => $client->client_code,
                'name' => $client->name,
                'pan' => $client->pan,
            ],
            'deleted client ' . $client->name,
        );
    }

    public function clientsBulkDeleted(array $clientIds): void
    {
        $this->log(
            'clients_bulk_deleted',
            null,
            [
                'client_ids' => $clientIds,
                'count' => count($clientIds),
            ],
            'bulk deleted ' . count($clientIds) . ' clients',
        );
    }

    public function userRoleChanged(User $user, string $previousRole, string $newRole): void
    {
        $this->log(
            'user_role_changed',
            $user,
            [
                'user_email' => $user->email,
                'user_name' => $user->name,
                'previous_role' => $previousRole,
                'new_role' => $newRole,
            ],
            "changed role for {$user->name} from {$previousRole} to {$newRole}",
        );
    }

    public function systemBackup(string $trigger = 'manual'): void
    {
        $this->log(
            'system_backup',
            null,
            ['trigger' => $trigger],
            "ran system backup ({$trigger})",
        );
    }

    public function moduleAccessChanged(User $user, array $previous, array $next): void
    {
        $changed = [];
        foreach ($next as $key => $enabled) {
            $was = (bool) ($previous[$key] ?? false);
            if ($was !== (bool) $enabled) {
                $changed[$key] = ['from' => $was, 'to' => (bool) $enabled];
            }
        }

        if ($changed === []) {
            return;
        }

        $this->log(
            'user_module_access_changed',
            $user,
            [
                'user_email' => $user->email,
                'user_name' => $user->name,
                'changes' => $changed,
            ],
            'updated module access for ' . $user->name,
        );
    }
}

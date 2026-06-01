<?php

namespace App\Services\Intelligence;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ServiceDue;
use App\Models\WhatsAppMessageLog;
use App\Services\WhatsAppService;
use Carbon\Carbon;

class WhatsAppInboundBot
{
    public function __construct(
        protected WhatsAppService $whatsapp,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('whatsapp.inbound_enabled');
    }

    /**
     * @return array{handled: bool, reply: ?string}
     */
    public function handleIncoming(string $fromPhone, string $messageBody, array $meta = []): array
    {
        $intent = $this->detectIntent($messageBody);
        $client = $this->resolveClient($fromPhone);

        WhatsAppMessageLog::create([
            'client_id' => $client?->id,
            'phone' => $fromPhone,
            'direction' => WhatsAppMessageLog::DIRECTION_IN,
            'body' => $messageBody,
            'intent' => $intent,
            'meta' => $meta,
        ]);

        $reply = $this->buildReply($client, $intent, $messageBody);

        $send = $this->whatsapp->sendMessage($fromPhone, $reply);

        WhatsAppMessageLog::create([
            'client_id' => $client?->id,
            'phone' => $fromPhone,
            'direction' => WhatsAppMessageLog::DIRECTION_OUT,
            'body' => $reply,
            'intent' => $intent,
            'meta' => ['send_success' => $send['success'] ?? false],
        ]);

        return [
            'handled' => true,
            'reply' => $reply,
            'client_id' => $client?->id,
            'intent' => $intent,
        ];
    }

    public function detectIntent(string $message): string
    {
        $text = strtolower(trim($message));

        if (preg_match('/\b(help|menu|options|start)\b/', $text)) {
            return 'help';
        }

        if (preg_match('/\b(hi|hello|hey|namaste)\b/', $text)) {
            return 'greeting';
        }

        if (preg_match('/\b(itr|gst|gstr|tds|compliance|due|overdue|pending|filing)\b/', $text)) {
            return 'compliance_status';
        }

        if (preg_match('/\b(invoice|payment|outstanding|balance|pay|bill|receipt)\b/', $text)) {
            return 'invoice_status';
        }

        if (preg_match('/\b(status|update|progress)\b/', $text)) {
            return 'general_status';
        }

        return 'unknown';
    }

    public function resolveClient(string $phone): ?Client
    {
        $digits = $this->normalizePhone($phone);
        if (strlen($digits) < 10) {
            return null;
        }

        $last10 = substr($digits, -10);

        $matches = Client::query()
            ->where('status', Client::STATUS_ACTIVE)
            ->where(function ($q) use ($last10, $digits) {
                $q->where('primary_contact_phone', 'like', '%' . $last10)
                    ->orWhere('primary_contact_phone', 'like', '%' . $digits);
            })
            ->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        return $matches->first(fn (Client $c) => $this->normalizePhone((string) $c->primary_contact_phone) === $digits)
            ?? $matches->first();
    }

    public function buildReply(?Client $client, string $intent, string $rawMessage): string
    {
        $firm = config('whatsapp.firm_reply_name', 'Your CA firm');

        if (! $client) {
            return "Hello from {$firm}.\n\nWe could not match your number to a client file. Please contact the office directly"
                . ($this->handoffLine() ? ' at ' . $this->handoffLine() : '')
                . ".\n\nReply HELP for options.";
        }

        return match ($intent) {
            'greeting', 'help' => $this->menuReply($client, $firm),
            'compliance_status', 'general_status' => $this->complianceReply($client, $firm),
            'invoice_status' => $this->invoiceReply($client, $firm),
            default => $this->menuReply($client, $firm)
                . "\n\n(Tip: try \"GST status\" or \"invoice balance\")",
        };
    }

    protected function menuReply(Client $client, string $firm): string
    {
        return "Hello {$client->name},\n\n{$firm} auto-reply:\n"
            . "• Reply *GST status* or *compliance* for pending filings\n"
            . "• Reply *invoice* or *payment* for outstanding bills\n"
            . "• A team member will follow up on complex queries\n"
            . $this->handoffLine();
    }

    protected function complianceReply(Client $client, string $firm): string
    {
        $dues = ServiceDue::query()
            ->whereIn('service_dues.status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $client->id))
            ->with('clientService.service')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        if ($dues->isEmpty()) {
            return "Hi {$client->name}, no pending compliance items on file at {$firm}. Thank you!";
        }

        $lines = $dues->map(fn ($d) => '• ' . ($d->clientService?->service?->name ?? 'Compliance')
            . ' — ' . $d->status
            . ' (' . $d->due_date?->format('d M Y') . ')')->implode("\n");

        $overdue = $dues->where('status', ServiceDue::STATUS_OVERDUE)->count();

        return "Compliance snapshot for {$client->name}:\n{$lines}"
            . ($overdue ? "\n\n⚠ {$overdue} overdue — our team will coordinate." : '')
            . "\n\n" . $this->handoffLine();
    }

    protected function invoiceReply(Client $client, string $firm): string
    {
        $invoices = Invoice::query()
            ->where('client_id', $client->id)
            ->whereIn('status', Invoice::OPEN_STATUSES)
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->orderByDesc('date')
            ->limit(5)
            ->get();

        if ($invoices->isEmpty()) {
            return "Hi {$client->name}, no open invoices on file at {$firm}.";
        }

        $lines = $invoices->map(function (Invoice $inv) {
            $bal = $inv->balanceDue();

            return '• ' . $inv->invoice_number . ' — ₹' . number_format($bal, 2) . ' (' . $inv->status . ')';
        })->implode("\n");

        $total = $invoices->sum(fn ($i) => $i->balanceDue());

        return "Open invoices for {$client->name}:\n{$lines}\n\nTotal outstanding: ₹" . number_format($total, 2)
            . "\n\nPay via UPI from your invoice PDF/link, or contact us.\n" . $this->handoffLine();
    }

    protected function handoffLine(): string
    {
        $phone = config('whatsapp.handoff_phone');

        return $phone ? "\nOffice: {$phone}" : '';
    }

    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}

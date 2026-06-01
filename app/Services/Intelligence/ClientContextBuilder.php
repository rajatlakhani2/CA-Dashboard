<?php

namespace App\Services\Intelligence;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceDue;
use App\Models\Task;

class ClientContextBuilder
{
    public function build(Client $client): array
    {
        $client->loadMissing(['manager', 'optedServices']);

        $openDues = ServiceDue::query()
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $client->id))
            ->whereIn('status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
            ->with('clientService.service')
            ->orderBy('due_date')
            ->limit(15)
            ->get();

        $activeTasks = Task::query()
            ->where('client_id', $client->id)
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->orderBy('due_date')
            ->limit(15)
            ->get();

        $openInvoices = Invoice::query()
            ->where('client_id', $client->id)
            ->whereIn('status', Invoice::OPEN_STATUSES)
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        $totalBilled = (float) Invoice::where('client_id', $client->id)->sum('total_amount');
        $totalCollected = (float) Payment::whereHas('invoice', fn ($q) => $q->where('client_id', $client->id))->sum('amount');
        $outstanding = max(0, $totalBilled - $totalCollected);

        return [
            'client' => [
                'name' => $client->name,
                'code' => $client->client_code,
                'category' => $client->category,
                'status' => $client->status,
                'manager' => $client->manager?->name,
                'pan_masked' => $this->maskPan($client->pan),
                'services' => $client->optedServices->pluck('name')->all(),
                'contact_phone' => $client->primary_contact_phone,
            ],
            'financial' => [
                'outstanding_inr' => round($outstanding, 2),
                'open_invoices' => $openInvoices->map(fn ($inv) => [
                    'number' => $inv->invoice_number,
                    'status' => $inv->status,
                    'amount' => (float) $inv->total_amount,
                    'date' => $inv->date?->format('Y-m-d'),
                ])->all(),
            ],
            'compliance_dues' => $openDues->map(fn ($due) => [
                'service' => $due->clientService?->service?->name,
                'status' => $due->status,
                'due_date' => $due->due_date?->format('Y-m-d'),
            ])->all(),
            'tasks' => $activeTasks->map(fn ($task) => [
                'title' => $task->title,
                'status' => $task->status,
                'due_date' => $task->due_date?->format('Y-m-d'),
            ])->all(),
        ];
    }

    public function toPromptText(array $context): string
    {
        return json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function maskPan(?string $pan): ?string
    {
        if (! $pan || strlen($pan) < 4) {
            return $pan;
        }

        return str_repeat('X', max(0, strlen($pan) - 4)) . substr($pan, -4);
    }
}

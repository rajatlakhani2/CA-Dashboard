<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceDue;
use App\Models\Task;
use Carbon\Carbon;

class ClientHealthScoreService
{
    /**
     * @return array{score: int, label: string, tone: string, breakdown: array<int, array{label: string, status: string, detail: string}>}
     */
    public function forClient(Client $client): array
    {
        $compliance = $this->complianceScore($client);
        $payments = $this->paymentsScore($client);
        $documents = $this->documentsScore($client);
        $tasks = $this->tasksScore($client);

        $score = (int) round(
            ($compliance['points'] * 0.4)
            + ($payments['points'] * 0.3)
            + ($documents['points'] * 0.2)
            + ($tasks['points'] * 0.1)
        );
        $score = max(0, min(100, $score));

        $breakdown = [
            ['label' => 'Compliance', 'status' => $compliance['status'], 'detail' => $compliance['detail']],
            ['label' => 'Payments', 'status' => $payments['status'], 'detail' => $payments['detail']],
            ['label' => 'Documents', 'status' => $documents['status'], 'detail' => $documents['detail']],
            ['label' => 'Tasks', 'status' => $tasks['status'], 'detail' => $tasks['detail']],
        ];

        return [
            'score' => $score,
            'label' => $score >= 80 ? 'Healthy' : ($score >= 60 ? 'Watch' : 'At risk'),
            'tone' => $score >= 80 ? 'green' : ($score >= 60 ? 'amber' : 'rose'),
            'breakdown' => $breakdown,
        ];
    }

    private function complianceScore(Client $client): array
    {
        $overdue = ServiceDue::query()
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $client->id))
            ->where('status', ServiceDue::STATUS_OVERDUE)
            ->count();
        $pending = ServiceDue::query()
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $client->id))
            ->where('status', ServiceDue::STATUS_PENDING)
            ->whereDate('due_date', '<=', Carbon::today())
            ->count();

        if ($overdue > 0) {
            return ['points' => 35, 'status' => 'red', 'detail' => "{$overdue} overdue filing(s)"];
        }
        if ($pending > 0) {
            return ['points' => 65, 'status' => 'amber', 'detail' => "{$pending} due now"];
        }

        return ['points' => 95, 'status' => 'green', 'detail' => 'On track'];
    }

    private function paymentsScore(Client $client): array
    {
        $billed = (float) $client->invoices()->sum('total_amount');
        $collected = (float) Payment::whereHas('invoice', fn ($q) => $q->where('client_id', $client->id))->sum('amount');
        $outstanding = max(0, $billed - $collected);
        $overdueInvoices = $client->invoices()->where('status', Invoice::STATUS_OVERDUE)->count();

        if ($overdueInvoices > 0) {
            return ['points' => 40, 'status' => 'red', 'detail' => '₹' . number_format($outstanding, 0) . ' overdue'];
        }
        if ($outstanding > 0) {
            return ['points' => 70, 'status' => 'amber', 'detail' => '₹' . number_format($outstanding, 0) . ' outstanding'];
        }

        return ['points' => 95, 'status' => 'green', 'detail' => 'Clear'];
    }

    private function documentsScore(Client $client): array
    {
        try {
            $summaries = app(ServiceDocumentChecklistService::class)->summariesForClient($client->id);
            $missing = collect($summaries)->sum('missing');
            if ($missing > 0) {
                return ['points' => 55, 'status' => 'amber', 'detail' => "{$missing} document(s) pending"];
            }
        } catch (\Throwable) {
            // checklist optional
        }

        return ['points' => 90, 'status' => 'green', 'detail' => 'Complete'];
    }

    private function tasksScore(Client $client): array
    {
        $overdue = $client->tasks()
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->whereDate('due_date', '<', Carbon::today())
            ->count();
        $open = $client->tasks()
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->count();

        if ($overdue > 0) {
            return ['points' => 45, 'status' => 'red', 'detail' => "{$overdue} overdue task(s)"];
        }
        if ($open > 5) {
            return ['points' => 70, 'status' => 'amber', 'detail' => "{$open} open tasks"];
        }

        return ['points' => 90, 'status' => 'green', 'detail' => $open > 0 ? "{$open} open" : 'Clear'];
    }
}

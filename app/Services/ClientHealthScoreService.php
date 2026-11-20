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
        return $this->forClients(collect([$client]))[$client->id];
    }

    /**
     * Health for many clients at once with a fixed handful of queries (the client list used to run
     * about 8 queries per client). Scores are identical to scoring each client on its own.
     *
     * @param  \Illuminate\Support\Collection<int, Client>  $clients
     * @return array<int, array{score: int, label: string, tone: string, breakdown: array}>
     */
    public function forClients(\Illuminate\Support\Collection $clients): array
    {
        $ids = $clients->pluck('id')->filter()->values()->all();
        if ($ids === []) {
            return [];
        }
        $today = Carbon::today()->toDateString();

        $duesBy = fn (callable $where) => $where(
            ServiceDue::query()
                ->join('client_services', 'client_services.id', '=', 'service_dues.client_service_id')
                ->whereIn('client_services.client_id', $ids)
        )->groupBy('client_services.client_id')
            ->selectRaw('client_services.client_id as cid, count(*) as n')
            ->pluck('n', 'cid');

        $overdueDues = $duesBy(fn ($q) => $q->where('service_dues.status', ServiceDue::STATUS_OVERDUE));
        $pendingDues = $duesBy(fn ($q) => $q->where('service_dues.status', ServiceDue::STATUS_PENDING)
            ->whereDate('service_dues.due_date', '<=', $today));

        $billed = Invoice::query()->whereIn('client_id', $ids)
            ->groupBy('client_id')->selectRaw('client_id as cid, sum(total_amount) as n')->pluck('n', 'cid');
        $overdueInvoices = Invoice::query()->whereIn('client_id', $ids)->where('status', Invoice::STATUS_OVERDUE)
            ->groupBy('client_id')->selectRaw('client_id as cid, count(*) as n')->pluck('n', 'cid');
        $collected = Payment::query()
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->whereNull('invoices.deleted_at')
            ->whereIn('invoices.client_id', $ids)
            ->groupBy('invoices.client_id')
            ->selectRaw('invoices.client_id as cid, sum(payments.amount) as n')
            ->pluck('n', 'cid');

        $openTasks = Task::query()->whereIn('client_id', $ids)->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->groupBy('client_id')->selectRaw('client_id as cid, count(*) as n')->pluck('n', 'cid');
        $overdueTasks = Task::query()->whereIn('client_id', $ids)->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->whereDate('due_date', '<', $today)
            ->groupBy('client_id')->selectRaw('client_id as cid, count(*) as n')->pluck('n', 'cid');

        $missingDocs = [];
        try {
            $checklist = app(ServiceDocumentChecklistService::class);
            \App\Models\ClientService::query()
                ->whereIn('client_id', $ids)
                ->where('status', \App\Models\ClientService::STATUS_ACTIVE)
                ->with(['service.documentRequirements', 'documentChecks'])
                ->get()
                ->each(function ($cs) use ($checklist, &$missingDocs) {
                    $summary = $checklist->summaryForClientService($cs);
                    if ($summary['total'] > 0) {
                        $missingDocs[$cs->client_id] = ($missingDocs[$cs->client_id] ?? 0) + $summary['missing'];
                    }
                });
        } catch (\Throwable) {
            $missingDocs = [];   // checklist optional
        }

        $out = [];
        foreach ($ids as $id) {
            $compliance = $this->complianceScore((int) ($overdueDues[$id] ?? 0), (int) ($pendingDues[$id] ?? 0));
            $payments = $this->paymentsScore((float) ($billed[$id] ?? 0), (float) ($collected[$id] ?? 0), (int) ($overdueInvoices[$id] ?? 0));
            $documents = $this->documentsScore((int) ($missingDocs[$id] ?? 0));
            $tasks = $this->tasksScore((int) ($overdueTasks[$id] ?? 0), (int) ($openTasks[$id] ?? 0));
            $out[$id] = $this->combine($compliance, $payments, $documents, $tasks);
        }

        return $out;
    }

    private function combine(array $compliance, array $payments, array $documents, array $tasks): array
    {
        $score = (int) round(
            ($compliance['points'] * 0.4)
            + ($payments['points'] * 0.3)
            + ($documents['points'] * 0.2)
            + ($tasks['points'] * 0.1)
        );
        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'label' => $score >= 80 ? 'Healthy' : ($score >= 60 ? 'Watch' : 'At risk'),
            'tone' => $score >= 80 ? 'green' : ($score >= 60 ? 'amber' : 'rose'),
            'breakdown' => [
                ['label' => 'Compliance', 'status' => $compliance['status'], 'detail' => $compliance['detail']],
                ['label' => 'Payments', 'status' => $payments['status'], 'detail' => $payments['detail']],
                ['label' => 'Documents', 'status' => $documents['status'], 'detail' => $documents['detail']],
                ['label' => 'Tasks', 'status' => $tasks['status'], 'detail' => $tasks['detail']],
            ],
        ];
    }

    private function complianceScore(int $overdue, int $pending): array
    {
        if ($overdue > 0) {
            return ['points' => 35, 'status' => 'red', 'detail' => "{$overdue} overdue filing(s)"];
        }
        if ($pending > 0) {
            return ['points' => 65, 'status' => 'amber', 'detail' => "{$pending} due now"];
        }

        return ['points' => 95, 'status' => 'green', 'detail' => 'On track'];
    }

    private function paymentsScore(float $billed, float $collected, int $overdueInvoices): array
    {
        $outstanding = max(0, $billed - $collected);

        if ($overdueInvoices > 0) {
            return ['points' => 40, 'status' => 'red', 'detail' => '₹' . number_format($outstanding, 0) . ' overdue'];
        }
        if ($outstanding > 0) {
            return ['points' => 70, 'status' => 'amber', 'detail' => '₹' . number_format($outstanding, 0) . ' outstanding'];
        }

        return ['points' => 95, 'status' => 'green', 'detail' => 'Clear'];
    }

    private function documentsScore(int $missing): array
    {
        if ($missing > 0) {
            return ['points' => 55, 'status' => 'amber', 'detail' => "{$missing} document(s) pending"];
        }

        return ['points' => 90, 'status' => 'green', 'detail' => 'Complete'];
    }

    private function tasksScore(int $overdue, int $open): array
    {
        if ($overdue > 0) {
            return ['points' => 45, 'status' => 'red', 'detail' => "{$overdue} overdue task(s)"];
        }
        if ($open > 5) {
            return ['points' => 70, 'status' => 'amber', 'detail' => "{$open} open tasks"];
        }

        return ['points' => 90, 'status' => 'green', 'detail' => $open > 0 ? "{$open} open" : 'Clear'];
    }
}

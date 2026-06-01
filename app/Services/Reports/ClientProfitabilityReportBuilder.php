<?php

namespace App\Services\Reports;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClientProfitabilityReportBuilder
{
    /**
     * @return array{rows: Collection, totals: array<string, float|int>}
     */
    public function build(User $actor, Carbon $start, Carbon $end, ?int $clientId = null): array
    {
        $clientsQuery = Client::query()->orderBy('name');
        ReportScopeHelper::scopeClients($clientsQuery, $actor);

        if ($clientId) {
            $clientsQuery->whereKey($clientId);
        }

        $rows = $clientsQuery->get()->map(function (Client $client) use ($start, $end, $actor) {
            $invoiceQuery = Invoice::query()
                ->where('client_id', $client->id)
                ->whereBetween('date', [$start, $end])
                ->where('status', '!=', Invoice::STATUS_DRAFT)
                ->where('status', '!=', Invoice::STATUS_CANCELLED);

            ReportScopeHelper::scopeInvoices($invoiceQuery, $actor);

            $revenue = (float) (clone $invoiceQuery)->sum('total_amount');

            $invoiceIds = (clone $invoiceQuery)->pluck('id');

            $collected = (float) Payment::query()
                ->whereIn('invoice_id', $invoiceIds)
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount');

            $openInvoices = Invoice::query()
                ->where('client_id', $client->id)
                ->whereIn('status', Invoice::OPEN_STATUSES)
                ->where('status', '!=', Invoice::STATUS_DRAFT);

            ReportScopeHelper::scopeInvoices($openInvoices, $actor);

            $outstanding = $openInvoices->get()->sum(fn (Invoice $i) => $i->balanceDue());

            $taskIds = Task::query()->where('client_id', $client->id)->pluck('id');

            $hours = (float) TimeEntry::query()
                ->whereIn('task_id', $taskIds)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->sum('hours');

            $realization = $revenue > 0 ? round(($collected / $revenue) * 100, 1) : ($collected > 0 ? 100 : 0);
            $revenuePerHour = $hours > 0 ? round($revenue / $hours, 0) : null;

            $lowMargin = ($hours >= 8 && $revenuePerHour !== null && $revenuePerHour < 1500)
                || ($revenue > 0 && $realization < 40 && $outstanding > $collected);

            return (object) [
                'client' => $client,
                'revenue' => $revenue,
                'collected' => $collected,
                'outstanding' => $outstanding,
                'hours' => round($hours, 1),
                'realization_rate' => $realization,
                'revenue_per_hour' => $revenuePerHour,
                'low_margin' => $lowMargin,
            ];
        })
            ->filter(fn ($row) => $row->revenue > 0 || $row->collected > 0 || $row->hours > 0 || $row->outstanding > 0)
            ->sortByDesc('revenue')
            ->values();

        return [
            'rows' => $rows,
            'totals' => [
                'revenue' => round($rows->sum('revenue'), 2),
                'collected' => round($rows->sum('collected'), 2),
                'outstanding' => round($rows->sum('outstanding'), 2),
                'hours' => round($rows->sum('hours'), 1),
                'low_margin_count' => $rows->where('low_margin', true)->count(),
            ],
        ];
    }
}

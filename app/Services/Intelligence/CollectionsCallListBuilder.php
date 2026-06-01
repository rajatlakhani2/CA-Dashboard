<?php

namespace App\Services\Intelligence;

use App\Models\Client;
use App\Models\CollectionFollowUp;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CollectionsCallListBuilder
{
    /**
     * @return Collection<int, object>
     */
    public function build(?string $bucket = null): Collection
    {
        $clients = Client::query()
            ->where('status', Client::STATUS_ACTIVE)
            ->get()
            ->keyBy('id');

        $rows = collect();

        Invoice::query()
            ->whereIn('status', Invoice::OPEN_STATUSES)
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->with(['payments', 'client'])
            ->chunkById(100, function ($invoices) use (&$rows, $clients) {
                foreach ($invoices->groupBy('client_id') as $clientId => $clientInvoices) {
                    $client = $clients->get($clientId) ?? $clientInvoices->first()?->client;
                    if (! $client) {
                        continue;
                    }

                    $outstanding = 0.0;
                    $oldestDue = null;

                    foreach ($clientInvoices as $invoice) {
                        $balance = $invoice->balanceDue();
                        if ($balance <= 0) {
                            continue;
                        }
                        $outstanding += $balance;
                        if ($invoice->due_date && ($oldestDue === null || $invoice->due_date->lt($oldestDue))) {
                            $oldestDue = $invoice->due_date;
                        }
                    }

                    if ($outstanding <= 0) {
                        continue;
                    }

                    $daysOverdue = ($oldestDue && $oldestDue->isPast())
                        ? (int) $oldestDue->diffInDays(Carbon::today())
                        : 0;

                    $agingBucket = $this->agingBucket($daysOverdue);
                    $lastContact = CollectionFollowUp::query()
                        ->where('client_id', $clientId)
                        ->max('contacted_at');

                    $daysSinceContact = $lastContact
                        ? (int) Carbon::parse($lastContact)->diffInDays(Carbon::today())
                        : ($oldestDue ? (int) $oldestDue->diffInDays(Carbon::today()) : 30);

                    $latestFollowUp = CollectionFollowUp::query()
                        ->where('client_id', $clientId)
                        ->latest('contacted_at')
                        ->first();

                    $priority = (int) round($outstanding / 1000) + ($daysSinceContact * 2) + ($daysOverdue * 3);

                    $rows->push((object) [
                        'client' => $client,
                        'outstanding' => $outstanding,
                        'oldest_due' => $oldestDue,
                        'days_overdue' => $daysOverdue,
                        'aging_bucket' => $agingBucket,
                        'days_since_contact' => $daysSinceContact,
                        'last_contact_at' => $lastContact ? Carbon::parse($lastContact) : null,
                        'latest_follow_up' => $latestFollowUp,
                        'promise_date' => $latestFollowUp?->promise_date,
                        'priority' => $priority,
                    ]);
                }
            });

        $sorted = $rows->sortByDesc('priority')->values();

        if ($bucket) {
            $sorted = $sorted->filter(fn ($row) => $row->aging_bucket === $bucket)->values();
        }

        return $sorted;
    }

    public function bucketCounts(): array
    {
        $all = $this->build();
        $counts = ['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];

        foreach ($all as $row) {
            $counts[$row->aging_bucket] = ($counts[$row->aging_bucket] ?? 0) + 1;
        }

        return $counts;
    }

    protected function agingBucket(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue > 90 => '90+',
            $daysOverdue > 60 => '61-90',
            $daysOverdue > 30 => '31-60',
            default => '0-30',
        };
    }
}

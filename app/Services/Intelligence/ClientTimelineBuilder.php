<?php

namespace App\Services\Intelligence;

use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\CollectionFollowUp;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceDue;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ClientTimelineBuilder
{
    /**
     * @return Collection<int, array{at: Carbon, type: string, title: string, detail: ?string, url: ?string}>
     */
    public function build(Client $client, int $limit = 40): Collection
    {
        $events = collect();

        Task::query()
            ->where('client_id', $client->id)
            ->latest()
            ->limit(15)
            ->get()
            ->each(function (Task $task) use ($events) {
                $events->push([
                    'at' => $task->created_at ?? now(),
                    'type' => 'task',
                    'title' => 'Task: ' . $task->title,
                    'detail' => $task->status . ($task->due_date ? ' · due ' . $task->due_date->format('d M Y') : ''),
                    'url' => route('tasks.edit', $task),
                ]);
            });

        ServiceDue::query()
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $client->id))
            ->with('clientService.service')
            ->latest()
            ->limit(15)
            ->get()
            ->each(function (ServiceDue $due) use ($events) {
                $name = $due->clientService?->service?->name ?? 'Compliance';
                $events->push([
                    'at' => $due->completed_at ?? $due->updated_at ?? $due->created_at,
                    'type' => 'compliance',
                    'title' => $name . ' — ' . $due->status,
                    'detail' => 'Due ' . $due->due_date?->format('d M Y'),
                    'url' => route('service-dues.index'),
                ]);
            });

        Invoice::query()
            ->where('client_id', $client->id)
            ->latest('date')
            ->limit(10)
            ->get()
            ->each(function (Invoice $invoice) use ($events) {
                $events->push([
                    'at' => Carbon::parse($invoice->date)->startOfDay(),
                    'type' => 'invoice',
                    'title' => 'Invoice ' . $invoice->invoice_number,
                    'detail' => $invoice->status . ' · ₹' . number_format($invoice->total_amount, 2),
                    'url' => route('invoices.show', $invoice),
                ]);
            });

        Payment::query()
            ->whereHas('invoice', fn ($q) => $q->where('client_id', $client->id))
            ->latest('payment_date')
            ->limit(10)
            ->with('invoice')
            ->get()
            ->each(function (Payment $payment) use ($events) {
                $events->push([
                    'at' => Carbon::parse($payment->payment_date)->startOfDay(),
                    'type' => 'payment',
                    'title' => 'Payment ' . $payment->receipt_number,
                    'detail' => '₹' . number_format($payment->amount, 2) . ' · ' . $payment->payment_mode,
                    'url' => route('payments.index'),
                ]);
            });

        $credentialIds = $client->credentials()->pluck('id');

        Activity::query()
            ->where(function ($q) use ($client, $credentialIds) {
                $q->where(function ($q2) use ($client) {
                    $q2->where('subject_type', Client::class)
                        ->where('subject_id', $client->id);
                });
                if ($credentialIds->isNotEmpty()) {
                    $q->orWhere(function ($q2) use ($credentialIds) {
                        $q2->where('subject_type', ClientCredential::class)
                            ->whereIn('subject_id', $credentialIds);
                    });
                }
            })
            ->latest()
            ->limit(15)
            ->get()
            ->each(function (Activity $activity) use ($events) {
                if ($activity->log_name === 'credential_vault') {
                    $events->push([
                        'at' => $activity->created_at,
                        'type' => 'credential',
                        'title' => 'Credential vault',
                        'detail' => $activity->description,
                        'url' => route('credentials.index'),
                    ]);

                    return;
                }

                if ($activity->subject_type === Client::class) {
                    $events->push([
                        'at' => $activity->created_at,
                        'type' => 'activity',
                        'title' => ucfirst($activity->event ?? 'update') . ' client record',
                        'detail' => $activity->description,
                        'url' => null,
                    ]);
                }
            });

        CollectionFollowUp::query()
            ->where('client_id', $client->id)
            ->latest('contacted_at')
            ->limit(10)
            ->get()
            ->each(function (CollectionFollowUp $followUp) use ($events) {
                $events->push([
                    'at' => $followUp->contacted_at,
                    'type' => 'collection',
                    'title' => 'Collection follow-up (' . $followUp->channel . ')',
                    'detail' => $followUp->notes ?? $followUp->next_action,
                    'url' => route('collections.index', ['client_id' => $followUp->client_id]),
                ]);
            });

        return $events
            ->sortByDesc(fn ($e) => $e['at']?->timestamp ?? 0)
            ->take($limit)
            ->values();
    }
}

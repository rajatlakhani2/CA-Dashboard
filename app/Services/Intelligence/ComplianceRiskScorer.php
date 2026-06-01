<?php

namespace App\Services\Intelligence;

use App\Models\Client;
use App\Models\ComplianceRiskScore;
use App\Models\ServiceDue;
use App\Models\Task;
use Carbon\Carbon;

class ComplianceRiskScorer
{
    public const LOOKAHEAD_DAYS = 14;

    public const MODEL_VERSION = 'v2';

    /**
     * @return array{scored: int}
     */
    public function score(): array
    {
        $fingerprints = [];
        $today = Carbon::today();
        $horizon = $today->copy()->addDays(self::LOOKAHEAD_DAYS);

        $clientIds = ServiceDue::query()
            ->whereIn('service_dues.status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
            ->where('due_date', '<=', $horizon)
            ->join('client_services', 'service_dues.client_service_id', '=', 'client_services.id')
            ->distinct()
            ->pluck('client_services.client_id');

        foreach ($clientIds as $clientId) {
            $client = Client::find($clientId);
            if (! $client || $client->status !== Client::STATUS_ACTIVE) {
                continue;
            }

            $dues = ServiceDue::query()
                ->whereIn('service_dues.status', [ServiceDue::STATUS_PENDING, ServiceDue::STATUS_OVERDUE])
                ->where('due_date', '<=', $horizon)
                ->whereHas('clientService', fn ($q) => $q->where('client_id', $clientId))
                ->with('clientService.service')
                ->get();

            foreach ($dues->groupBy(fn ($d) => $d->clientService?->service_id) as $serviceId => $serviceDues) {
                if (! $serviceId) {
                    continue;
                }

                $result = $this->scoreServiceDues($client, (int) $serviceId, $serviceDues);
                $fingerprint = 'risk:' . $clientId . ':' . $serviceId;
                $fingerprints[] = $fingerprint;

                ComplianceRiskScore::query()->updateOrCreate(
                    ['fingerprint' => $fingerprint],
                    [
                        'client_id' => $clientId,
                        'service_id' => $serviceId,
                        'score' => $result['score'],
                        'level' => $result['level'],
                        'predicted_miss' => $result['predicted_miss'],
                        'model_version' => self::MODEL_VERSION,
                        'signals' => $result['signals'],
                        'next_due_date' => $result['next_due'],
                        'scored_at' => now(),
                    ]
                );
            }
        }

        ComplianceRiskScore::query()
            ->where('scored_at', '<', now()->subDay())
            ->whereNotIn('fingerprint', $fingerprints)
            ->delete();

        return ['scored' => count($fingerprints)];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ServiceDue>  $dues
     * @return array{score: int, level: string, predicted_miss: bool, signals: array, next_due: ?\Carbon\Carbon}
     */
    protected function scoreServiceDues(Client $client, int $serviceId, $dues): array
    {
        $signals = [];
        $score = 0;

        $overdueCount = $dues->where('status', ServiceDue::STATUS_OVERDUE)->count();
        if ($overdueCount > 0) {
            $score += min(40, $overdueCount * 15);
            $signals[] = "{$overdueCount} overdue due(s)";
        }

        $pendingCount = $dues->where('status', ServiceDue::STATUS_PENDING)->count();
        if ($pendingCount >= 3) {
            $score += 10;
            $signals[] = "{$pendingCount} pending items (velocity)";
        }

        $nextDue = $dues->min('due_date');
        if ($nextDue && $nextDue->isPast()) {
            $daysLate = (int) $nextDue->diffInDays(Carbon::today());
            $score += min(25, $daysLate);
            $signals[] = "Next due {$daysLate}d past";
        } elseif ($nextDue && $nextDue->diffInDays(Carbon::today()) <= 7) {
            $score += 15;
            $signals[] = 'Due within 7 days';
        }

        $categoryBoost = match (strtoupper((string) $client->category)) {
            'A' => 12,
            'B' => 6,
            default => 0,
        };
        if ($categoryBoost > 0) {
            $score += $categoryBoost;
            $signals[] = "Category {$client->category} client weighting";
        }

        $lateHistoryRate = $this->lateCompletionRate($client->id, $serviceId);
        if ($lateHistoryRate >= 0.5) {
            $score += 20;
            $signals[] = 'Often completed late historically';
        } elseif ($lateHistoryRate >= 0.25) {
            $score += 10;
            $signals[] = 'Sometimes completed late';
        }

        if ($this->hasConsecutiveLateStreak($client->id, $serviceId, 3)) {
            $score += 15;
            $signals[] = 'Last 3 filings completed late';
        }

        $openLateTasks = Task::query()
            ->where('client_id', $client->id)
            ->whereNotIn('status', Task::TERMINAL_STATUSES)
            ->where('due_date', '<', Carbon::today())
            ->count();

        if ($openLateTasks > 0) {
            $score += min(15, $openLateTasks * 5);
            $signals[] = "{$openLateTasks} overdue task(s)";
        }

        $score = min(100, $score);
        $level = match (true) {
            $score >= 60 => ComplianceRiskScore::LEVEL_HIGH,
            $score >= 35 => ComplianceRiskScore::LEVEL_MEDIUM,
            default => ComplianceRiskScore::LEVEL_LOW,
        };

        return [
            'score' => $score,
            'level' => $level,
            'predicted_miss' => $score >= 60,
            'signals' => $signals,
            'next_due' => $nextDue,
        ];
    }

    protected function hasConsecutiveLateStreak(int $clientId, int $serviceId, int $streakLength): bool
    {
        $completed = ServiceDue::query()
            ->where('status', ServiceDue::STATUS_COMPLETED)
            ->whereNotNull('completed_at')
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $clientId)->where('service_id', $serviceId))
            ->orderByDesc('completed_at')
            ->limit($streakLength)
            ->get();

        if ($completed->count() < $streakLength) {
            return false;
        }

        return $completed->every(
            fn ($due) => $due->completed_at && $due->due_date && $due->completed_at->gt($due->due_date)
        );
    }

    protected function lateCompletionRate(int $clientId, int $serviceId): float
    {
        $completed = ServiceDue::query()
            ->where('status', ServiceDue::STATUS_COMPLETED)
            ->whereNotNull('completed_at')
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $clientId)->where('service_id', $serviceId))
            ->limit(20)
            ->get();

        if ($completed->isEmpty()) {
            return 0;
        }

        $late = $completed->filter(fn ($due) => $due->completed_at && $due->due_date && $due->completed_at->gt($due->due_date))->count();

        return $late / $completed->count();
    }

    /**
     * Top at-risk rows for dashboard.
     */
    public function topAtRisk(int $limit = 8)
    {
        return ComplianceRiskScore::query()
            ->with(['client', 'service'])
            ->whereIn('level', [ComplianceRiskScore::LEVEL_HIGH, ComplianceRiskScore::LEVEL_MEDIUM])
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }
}

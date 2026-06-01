<?php

namespace App\Services;

use App\Models\ClientService;
use App\Models\Client;
use App\Models\ServiceDue;
use Carbon\Carbon;

class ServiceDueGenerator
{
    /**
     * Generate upcoming dues for all active client services.
     */
    public function generateAll()
    {
        $activeServices = ClientService::with(['client', 'service'])
            ->where('status', ClientService::STATUS_ACTIVE)
            ->get();

        $generatedCount = 0;

        foreach ($activeServices as $clientService) {
            if ($this->generateForClientService($clientService)) {
                $generatedCount++;
            }
        }

        return $generatedCount;
    }

    /**
     * Generate due for a specific client service if applicable.
     * Logic:
     * 1. Determine next due date based on frequency.
     * 2. Check if a due already exists for that period.
     * 3. Create if missing.
     */
    public function generateForClientService(ClientService $clientService)
    {
        // Avoid generating for inactive or closed clients
        if ($clientService->client->status !== Client::STATUS_ACTIVE) {
            return false;
        }

        $service = $clientService->service;
        $dueDay = $clientService->custom_due_day ?? $service->due_day ?? 1; // Default to 1st if missing

        $nextDueDate = $this->calculateNextDueDate($service->frequency, $dueDay, $service->due_month);

        // Check if this specific due date already exists
        $exists = ServiceDue::where('client_service_id', $clientService->id)
            ->whereDate('due_date', $nextDueDate)
            ->exists();

        if (!$exists) {
            ServiceDue::create([
                'client_service_id' => $clientService->id,
                'due_date' => $nextDueDate,
                'status' => ServiceDue::STATUS_PENDING,
            ]);
            return true;
        }

        return false;
    }

    private function calculateNextDueDate($frequency, $dueDay, $dueMonth = null)
    {
        $today = Carbon::now()->startOfDay();

        // Handle Annually separately to avoid month overflow issues during intermediate steps
        if ($frequency === 'Annually') {
            if ($dueMonth) {
                $date = Carbon::create($today->year, $dueMonth, $dueDay, 0, 0, 0);
                if ($today->gt($date)) {
                    $date->addYear();
                }
                return $date;
            }
            // If no dueMonth, fall through (though Annual usually has dueMonth)
        }

        // For Monthly/Quarterly, start with this month's tentative due date
        // Note: overflow behavior (e.g. Feb 31 -> Mar 3) is default Carbon behavior.
        // We accept this for now or could use createSafe() if strict validation needed.
        $date = Carbon::create($today->year, $today->month, $dueDay, 0, 0, 0);

        // Adjust based on frequency
        switch ($frequency) {
            case 'Monthly':
                if ($today->gt($date)) {
                    $date->addMonth();
                }
                break;

            case 'Quarterly':
                if ($today->gt($date)) {
                    $date->addMonths(3);
                }
                break;

            case 'Half-Yearly':
                if ($today->gt($date)) {
                    $date->addMonths(6);
                }
                break;

            case 'Annually':
                // Fallback for Annually without dueMonth (from original code logic)
                $date->addYear();
                break;

            case 'One-Time':
                // For One-Time, if the date is passed, we don't "reschedule" it.
                // It stays as the original calculated date.
                // The duplication check in generateForClientService will prevent re-creation.
                break;
        }

        return $date;
    }
}

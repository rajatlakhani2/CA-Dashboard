<?php

namespace App\Support;

use App\Models\BillingRule;
use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientCredential;
use App\Models\ClientDocument;
use App\Models\ClientService;
use App\Models\ClientServiceDocumentCheck;
use App\Models\ClientWorksheet;
use App\Models\CollectionFollowUp;
use App\Models\ComplianceRiskScore;
use App\Models\Dsc;
use App\Models\DocumentIngestion;
use App\Models\Expense;
use App\Models\FirmAlert;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\OnboardingChecklist;
use App\Models\Payment;
use App\Models\PersonalRenewal;
use App\Models\Service;
use App\Models\ServiceDocumentRequirement;
use App\Models\ServiceDue;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\TdsEntry;
use App\Models\TimeEntry;
use App\Models\WhatsAppMessageLog;

/**
 * Eloquent models scoped to the current organization (tenant).
 */
final class TenantModels
{
    /** @return list<class-string<\Illuminate\Database\Eloquent\Model>> */
    public static function scoped(): array
    {
        return [
            Client::class,
            Task::class,
            Invoice::class,
            Branch::class,
            ServiceDue::class,
            Payment::class,
            Setting::class,
            FirmAlert::class,
            Service::class,
            ClientService::class,
            ClientContact::class,
            ClientDocument::class,
            ClientCredential::class,
            Dsc::class,
            PersonalRenewal::class,
            Subscription::class,
            Leave::class,
            TimeEntry::class,
            Expense::class,
            TdsEntry::class,
            OnboardingChecklist::class,
            TaskTemplate::class,
            BillingRule::class,
            ClientWorksheet::class,
            CollectionFollowUp::class,
            ComplianceRiskScore::class,
            DocumentIngestion::class,
            ServiceDocumentRequirement::class,
            ClientServiceDocumentCheck::class,
            WhatsAppMessageLog::class,
        ];
    }
}

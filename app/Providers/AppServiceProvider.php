<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientCredential;
use App\Models\Dsc;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Scopes\OrganizationScope;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\TdsEntry;
use App\Models\User;
use App\Support\TenantModels;
use App\Policies\BranchPolicy;
use App\Policies\ClientCredentialPolicy;
use App\Policies\ClientPolicy;
use App\Policies\DscPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ReportPolicy;
use App\Policies\SettingPolicy;
use App\Policies\StaffPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TdsEntryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(ClientCredential::class, ClientCredentialPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Dsc::class, DscPolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(User::class, StaffPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TdsEntry::class, TdsEntryPolicy::class);

        Gate::define('viewReports', [ReportPolicy::class, 'view']);
        Gate::define('exportReports', [ReportPolicy::class, 'export']);

        foreach (TenantModels::scoped() as $model) {
            $model::addGlobalScope(new OrganizationScope);
        }
    }
}

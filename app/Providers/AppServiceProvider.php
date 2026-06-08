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
use App\Models\Organization;
use App\Models\Setting;
use App\Support\OrganizationContext;
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
use App\Services\NotificationSummaryService;
use App\Support\Branding;
use App\Support\ThemePreset;
use App\Support\WorkspaceProfile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        $appUrl = (string) config('app.url');
        if ($appUrl !== '') {
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

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

        View::composer(['layouts.app', 'auth.register', 'welcome', 'demo.theme-gallery'], function ($view) {
            $view->with([
                'dashboardBrandName' => Branding::dashboardName(),
                'dashboardBrandTagline' => Branding::dashboardTagline(),
                'workspaceType' => WorkspaceProfile::current(),
                'themePreset' => ThemePreset::forWorkspaceType(),
            ]);
        });

        View::composer('auth.login', function ($view) {
            $workspace = old('workspace', session('workspace_slug', request('workspace')));
            $brandName = Branding::DEFAULT_NAME;
            $brandTagline = 'Multi-firm workspace';
            $workspaceType = WorkspaceProfile::TYPE_CA_FIRM;
            $themePreset = ThemePreset::slatePro();

            if ($workspace) {
                $org = Organization::where('slug', strtolower(trim((string) $workspace)))
                    ->where('is_active', true)
                    ->first();

                if ($org) {
                    OrganizationContext::set($org->id);
                    $brandName = Branding::dashboardName();
                    $brandTagline = Branding::dashboardTagline();
                    $workspaceType = WorkspaceProfile::current();
                    $themePreset = ThemePreset::forWorkspaceType($workspaceType);
                    OrganizationContext::clear();
                }
            }

            $view->with([
                'dashboardBrandName' => $brandName,
                'dashboardBrandTagline' => $brandTagline,
                'workspaceType' => $workspaceType,
                'themePreset' => $themePreset,
            ]);
        });

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            if ($user) {
                $view->with('notificationGroups', app(NotificationSummaryService::class)->groups());
            }
        });
    }
}

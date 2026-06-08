<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Organization;
use App\Models\Setting;
use App\Support\OrganizationContext;

$organization = Organization::where('slug', 'rla')->first()
    ?? Organization::query()->first();

if (! $organization) {
    fwrite(STDERR, "No organization found. Run database seeders first.\n");
    exit(1);
}

OrganizationContext::set($organization->id);

Setting::set('workspace_type', 'executive');
Setting::set('dashboard_name', 'Vouchex');
Setting::set('dashboard_tagline', 'Finance, compliance & reminders — one workspace.');
Setting::set('enabled_modules', json_encode([
    'dashboard' => true,
    'clients' => true,
    'tasks' => true,
    'service_dues' => true,
    'personal_renewals' => true,
    'smart_documents' => false,
    'invoices' => false,
    'billing' => false,
    'payments' => false,
    'expenses' => false,
    'reports' => true,
    'staff' => false,
    'credentials' => false,
    'compliance' => true,
    'dsc' => true,
    'tds' => false,
    'subscriptions' => false,
    'activity' => true,
    'settings' => true,
    'system' => false,
]));

echo "Demo branding + executive modules seeded for org #{$organization->id} ({$organization->slug}).\n";

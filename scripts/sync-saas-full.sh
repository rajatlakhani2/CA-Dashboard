#!/bin/bash
# Full multi-tenant SaaS deploy from GitHub (no git). Fixes missing TenantModels etc.
set -e

BASE="https://raw.githubusercontent.com/rajatlakhani2/CA-Dashboard/master"

if [ -z "$APP" ]; then
  for candidate in "$HOME/app.kuhu.org.in" "$HOME/public_html/app.kuhu.org.in" "$(pwd)"; do
    if [ -f "$candidate/artisan" ]; then APP="$candidate"; break; fi
  done
fi

if [ ! -f "$APP/artisan" ]; then
  echo "ERROR: artisan not found. export APP=\$HOME/app.kuhu.org.in"
  exit 1
fi

cd "$APP"
echo "==> Full SaaS sync: $(pwd)"

mkdir -p scripts app/Support app/Services app/Http/{Controllers,Middleware,Requests} \
  app/Models/{Concerns,Scopes} app/Providers app/Console/Commands \
  resources/views/{auth,dashboard/partials,system} database/{migrations,seeders} routes

FILES=(
  app/Support/TenantModels.php
  app/Support/OrganizationContext.php
  app/Services/OrganizationWorkspaceService.php
  app/Services/OrganizationRegistrationService.php
  app/Http/Middleware/EnsureOrganizationIsActive.php
  app/Http/Middleware/SetOrganizationContext.php
  app/Http/Controllers/LoginController.php
  app/Http/Controllers/RegisterOrganizationController.php
  app/Http/Controllers/DashboardController.php
  app/Http/Controllers/StaffController.php
  app/Http/Controllers/SystemController.php
  app/Http/Requests/StoreStaffRequest.php
  app/Console/Commands/ShowOrganizationSlug.php
  app/Providers/AppServiceProvider.php
  app/Models/Organization.php
  app/Models/Concerns/BelongsToOrganization.php
  app/Models/Scopes/OrganizationScope.php
  app/Models/User.php
  app/Models/Client.php
  app/Models/Task.php
  app/Models/Invoice.php
  app/Models/Branch.php
  app/Models/ServiceDue.php
  app/Models/Payment.php
  app/Models/Setting.php
  app/Models/FirmAlert.php
  app/Models/Service.php
  app/Models/ClientService.php
  app/Models/ClientContact.php
  app/Models/ClientDocument.php
  app/Models/ClientCredential.php
  app/Models/Dsc.php
  app/Models/PersonalRenewal.php
  app/Models/Subscription.php
  app/Models/Leave.php
  app/Models/TimeEntry.php
  app/Models/Expense.php
  app/Models/TdsEntry.php
  app/Models/OnboardingChecklist.php
  app/Models/TaskTemplate.php
  app/Models/BillingRule.php
  app/Models/ClientWorksheet.php
  app/Models/CollectionFollowUp.php
  app/Models/ComplianceRiskScore.php
  app/Models/DocumentIngestion.php
  app/Models/ServiceDocumentRequirement.php
  app/Models/ClientServiceDocumentCheck.php
  app/Models/WhatsAppMessageLog.php
  routes/web.php
  resources/views/auth/login.blade.php
  resources/views/auth/register-organization.blade.php
  resources/views/dashboard.blade.php
  resources/views/dashboard/partials/premium-styles.blade.php
  resources/views/dashboard/partials/workspace-header.blade.php
  resources/views/partials/premium-fonts.blade.php
  resources/css/app.css
  resources/views/system/index.blade.php
  database/migrations/2026_06_03_120000_create_organizations_multi_tenant.php
  database/migrations/2026_06_04_100000_extend_organization_tenancy_tables.php
  database/seeders/FirmTeamSeeder.php
  database/migrations/2026_06_04_120000_add_onboarding_to_organizations.php
  app/Services/DashboardMissionControlService.php
  app/Services/PartnerFirmOverviewService.php
  app/Http/Controllers/PartnerDashboardController.php
  app/Services/ClientHealthScoreService.php
  app/Services/NotificationSummaryService.php
  app/Services/WorkspaceOnboardingService.php
  app/Services/CommandPaletteBuilder.php
  app/Http/Controllers/WorkspaceOnboardingController.php
  app/Http/Controllers/ClientController.php
  routes/modules/operations.php
  resources/views/dashboard/partials/mission-control.blade.php
  resources/views/dashboard/partials/firm-overview.blade.php
  resources/views/dashboard/partials/firm-pulse.blade.php
  resources/views/dashboard/partials/revenue-command-center.blade.php
  resources/views/dashboard/partials/onboarding-banner.blade.php
  resources/views/clients/index.blade.php
  resources/views/clients/show.blade.php
  resources/views/clients/partials/health-score.blade.php
  resources/views/clients/partials/whatsapp-quick-actions.blade.php
  resources/views/layouts/app.blade.php
  resources/views/partials/mobile-bottom-nav.blade.php
  resources/views/partials/mobile-fab.blade.php
  resources/views/partials/command-palette.blade.php
)

for path in "${FILES[@]}"; do
  curl -fsSL -o "$path" "$BASE/$path"
  echo "  ok $path"
done

echo "==> Autoload + migrate + cache"
if command -v composer >/dev/null 2>&1; then
  composer dump-autoload --no-interaction 2>/dev/null || composer dump-autoload
fi
php artisan migrate --force
php artisan optimize:clear
php artisan view:clear
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo \"OPcache reset\n\"; }"

if [ ! -f app/Support/TenantModels.php ]; then
  echo "ERROR: TenantModels.php still missing"
  exit 1
fi

php artisan organization:slug --set=rla 2>/dev/null || true

echo ""
echo "SUCCESS: Full SaaS bundle on disk."
echo "Login: Workspace ID = rla (run: php artisan organization:slug)"
echo "New firms: https://app.kuhu.org.in/register"
echo "Run migrate output above — both organization migrations should be Ran."

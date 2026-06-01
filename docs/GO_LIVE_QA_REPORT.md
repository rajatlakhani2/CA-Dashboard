# Go-Live QA Report

**Date:** 2026-05-30  
**Application:** CA Dashboard (RLA Dashboard v2.1)  
**Environment tested:** Local (`127.0.0.1:8888` PHP built-in server + Vite `public/build`)

---

## Executive summary

| Gate | Result |
|------|--------|
| PHPUnit (full suite) | **272 passed**, 1008 assertions |
| Go-live route catalog (`GoLiveReadinessTest`) | **57 passed** (50 partner pages + role matrix + artisan) |
| Frontend production build (`npm run build`) | **Passed** (PWA assets generated) |
| Migrations (`artisan migrate --force`) | **Passed** (incl. `2026_05_29_200000` indexes) |
| Live browser QA (`npm run test:browser`) | **72 passed**, 0 failed |
| Production URL smoke (`https://kuhu.org.in`) | **Partial** — see [PRODUCTION_SMOKE_LOG.md](./PRODUCTION_SMOKE_LOG.md) (`app.kuhu.org.in` → 404) |

**Recommendation:** Safe to deploy from a code/test perspective. Complete **production smoke** and **change default passwords** on the live server before announcing go-live.

---

## Automated coverage map

### PHPUnit modules exercised

- **Security & roles:** `SecurityAccessTest`, `FirmLiveQATest`, `FirmRolesTest`, `PolicyAccessTest`, `ClientVisibilityTest`, `StaffPolicyTest`, `TaskPolicyTest`
- **Finance:** `InvoiceFeatureTest`, `FinanceCompliancePolicyTest`, `NewerModuleFlowTest`, `SensitiveActionAuditTest`
- **Compliance:** `ServiceDueTest`, `ServiceDocumentChecklistTest`, `PersonalRenewalTest`, `DashboardCalendarFilterTest`
- **Operations:** `ClientModuleTest`, `ClientImportPreviewTest`, `CredentialVaultAuditTest`, `SystemBackupTest`, `DeepPostFlowTest`
- **Productivity:** `WorkloadPlannerTest`, `ProductivityProfitabilityReportTest`, `MobileWorkModeTest`, `CommandPaletteTest`
- **Intelligence:** `IntelligencePhaseOneTest` … `IntelligencePhaseFourTest`, `AnomalyScannerTest`
- **Catalog:** `FullApplicationCatalogTest`, `GoLiveReadinessTest`

### Browser QA (Playwright)

Partner: all primary sidebar modules + reports + workload + collections + palette API + client drill-down.  
Associate: allowed modules + finance/admin 403 checks.  
Article: My Day landing, tasks, client create, blocked list/dashboard/billing.

```powershell
.\.tools\php\php.exe -S 127.0.0.1:8888 -t public
$env:BASE_URL='http://127.0.0.1:8888'
npm run test:browser
```

---

## Pre-deploy checklist (run on server)

1. `composer install --no-dev --optimize-autoloader`
2. `php artisan migrate --force`
3. `php artisan users:ensure-firm-logins`
4. Upload `public/build/` from `npm run build`
5. Set `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_ALLOW_DANGEROUS_SYSTEM=false`
6. `php artisan config:cache` / `route:cache` / `view:cache`
7. Cron: `* * * * * php artisan schedule:run`
8. Manual smoke: login partner → dashboard → clients → billing → sign out

See [DEPLOY_CHECKLIST.md](./DEPLOY_CHECKLIST.md) for full steps.

---

## Known limitations (not blocking deploy)

| Item | Notes |
|------|--------|
| E-invoice / IRN | Out of scope |
| Workload calendar grid / estimated hours | MVP only (`/workload` kanban) |
| Full offline PWA | Manifest + mobile meta; no service-worker offline queue |
| WhatsApp live | Requires Meta credentials + webhook on production |
| Production URL | Re-test `https://app.kuhu.org.in` from network that reaches hosting |

---

## Manual smoke (15 min on production)

Use [DASHBOARD_FEATURE_TEST.md](./DASHBOARD_FEATURE_TEST.md) register. Minimum path:

1. Partner login → Overview KPIs → one client show (Work tab checklist)
2. Create draft invoice from billing OR record payment
3. Service due list → missing-doc link opens client checklist
4. Associate login → clients (own only) → invoices read-only → billing 403
5. Article login → My Day → submit client (pending approval)
6. System → backup list (partner only)
7. Sign out all roles

---

## Commands reference

```powershell
cd "D:\New folder\Dashboard\CA Dashboard"
.\.tools\php\php.exe artisan test
npm run build
.\.tools\php\php.exe artisan users:ensure-firm-logins
```

**Default QA passwords:** `password` — change after go-live.

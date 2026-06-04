<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'services',
        'client_services',
        'client_contacts',
        'client_documents',
        'client_credentials',
        'dscs',
        'personal_renewals',
        'subscriptions',
        'leaves',
        'time_entries',
        'expenses',
        'tds_entries',
        'onboarding_checklists',
        'task_templates',
        'billing_rules',
        'client_worksheets',
        'collection_follow_ups',
        'compliance_risk_scores',
        'document_ingestions',
        'client_portal_tokens',
        'service_document_requirements',
        'client_service_document_checks',
        'whatsapp_message_logs',
        'invoice_items',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'organization_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        $orgId = DB::table('organizations')->orderBy('id')->value('id');
        if (! $orgId) {
            return;
        }

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'organization_id')) {
                DB::table($table)->whereNull('organization_id')->update(['organization_id' => $orgId]);
            }
        }

        $this->backfillFromClients($orgId);
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'organization_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('organization_id');
            });
        }
    }

    private function backfillFromClients(int $orgId): void
    {
        if (! Schema::hasTable('clients') || ! Schema::hasColumn('clients', 'organization_id')) {
            return;
        }

        $pairs = [
            'client_services' => 'client_id',
            'client_contacts' => 'client_id',
            'client_documents' => 'client_id',
            'client_credentials' => 'client_id',
            'subscriptions' => 'client_id',
            'client_worksheets' => 'client_id',
            'onboarding_checklists' => 'client_id',
            'compliance_risk_scores' => 'client_id',
        ];

        foreach ($pairs as $table => $fk) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'organization_id')) {
                continue;
            }
            $this->copyOrgFromParent($table, $fk, 'clients', 'id', 'organization_id');
            DB::table($table)->whereNull('organization_id')->update(['organization_id' => $orgId]);
        }

        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'organization_id')) {
            $this->copyOrgFromParent('invoice_items', 'invoice_id', 'invoices', 'id', 'organization_id');
            DB::table('invoice_items')->whereNull('organization_id')->update(['organization_id' => $orgId]);
        }

        if (Schema::hasTable('service_dues') && Schema::hasColumn('service_dues', 'organization_id')) {
            $this->copyOrgFromParent('service_dues', 'client_service_id', 'client_services', 'id', 'organization_id');
        }
    }

    private function copyOrgFromParent(
        string $childTable,
        string $childFk,
        string $parentTable,
        string $parentPk,
        string $orgColumn,
    ): void {
        $rows = DB::table($childTable)->whereNull($orgColumn)->select(['id', $childFk])->get();
        foreach ($rows as $row) {
            $parentOrg = DB::table($parentTable)
                ->where($parentPk, $row->{$childFk})
                ->value($orgColumn);
            if ($parentOrg) {
                DB::table($childTable)->where('id', $row->id)->update([$orgColumn => $parentOrg]);
            }
        }
    }
};

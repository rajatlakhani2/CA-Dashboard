<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->safeIndex('clients', 'manager_id');
        $this->safeIndex('clients', 'branch_id');
        $this->safeIndex('clients', 'status');
        $this->safeIndex('clients', 'category');

        $this->safeIndex('tasks', 'client_id');
        $this->safeIndex('tasks', 'assigned_to');
        $this->safeIndex('tasks', 'created_by');
        $this->safeIndex('tasks', 'status');
        $this->safeIndex('tasks', 'due_date');
        $this->safeIndex('tasks', 'invoice_id');

        $this->safeIndex('invoices', 'client_id');
        $this->safeIndex('invoices', 'branch_id');
        $this->safeIndex('invoices', 'status');

        $this->safeIndex('payments', 'invoice_id');
        $this->safeIndex('payments', 'status');

        $this->safeIndex('users', 'branch_id');
        $this->safeIndex('users', 'role');

        $this->safeIndex('client_credentials', 'client_id');
        $this->safeIndex('dscs', 'client_id');
        $this->safeIndex('dscs', 'expiry_date');
        $this->safeIndex('tds_entries', 'invoice_id');
        $this->safeIndex('subscriptions', 'client_id');
        $this->safeIndex('subscriptions', 'status');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('clients', 'clients_manager_id_index');
        $this->dropIndexIfExists('clients', 'clients_branch_id_index');
        $this->dropIndexIfExists('clients', 'clients_status_index');
        $this->dropIndexIfExists('clients', 'clients_category_index');

        $this->dropIndexIfExists('tasks', 'tasks_client_id_index');
        $this->dropIndexIfExists('tasks', 'tasks_assigned_to_index');
        $this->dropIndexIfExists('tasks', 'tasks_created_by_index');
        $this->dropIndexIfExists('tasks', 'tasks_status_index');
        $this->dropIndexIfExists('tasks', 'tasks_due_date_index');
        $this->dropIndexIfExists('tasks', 'tasks_invoice_id_index');

        $this->dropIndexIfExists('invoices', 'invoices_client_id_index');
        $this->dropIndexIfExists('invoices', 'invoices_branch_id_index');
        $this->dropIndexIfExists('invoices', 'invoices_status_index');

        $this->dropIndexIfExists('payments', 'payments_invoice_id_index');
        $this->dropIndexIfExists('payments', 'payments_status_index');

        $this->dropIndexIfExists('users', 'users_branch_id_index');
        $this->dropIndexIfExists('users', 'users_role_index');

        $this->dropIndexIfExists('client_credentials', 'client_credentials_client_id_index');
        $this->dropIndexIfExists('dscs', 'dscs_client_id_index');
        $this->dropIndexIfExists('dscs', 'dscs_expiry_date_index');
        $this->dropIndexIfExists('tds_entries', 'tds_entries_invoice_id_index');
        $this->dropIndexIfExists('subscriptions', 'subscriptions_client_id_index');
        $this->dropIndexIfExists('subscriptions', 'subscriptions_status_index');
    }

    private function safeIndex(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $indexName = "{$table}_{$column}_index";

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->index($column);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$table, $indexName]
        );

        return count($rows) > 0;
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }
};

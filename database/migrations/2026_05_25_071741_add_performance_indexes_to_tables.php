<?php

use App\Support\SafeSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SafeSchema::addIndex('clients', 'manager_id');
        SafeSchema::addIndex('clients', 'branch_id');
        SafeSchema::addIndex('clients', 'status');
        SafeSchema::addIndex('clients', 'category');

        SafeSchema::addIndex('tasks', 'client_id');
        SafeSchema::addIndex('tasks', 'assigned_to');
        SafeSchema::addIndex('tasks', 'created_by');
        SafeSchema::addIndex('tasks', 'status');
        SafeSchema::addIndex('tasks', 'due_date');
        SafeSchema::addIndex('tasks', 'invoice_id');

        SafeSchema::addIndex('invoices', 'client_id');
        SafeSchema::addIndex('invoices', 'branch_id');
        SafeSchema::addIndex('invoices', 'status');

        SafeSchema::addIndex('payments', 'invoice_id');
        SafeSchema::addIndex('payments', 'status');

        SafeSchema::addIndex('users', 'branch_id');
        SafeSchema::addIndex('users', 'role');

        SafeSchema::addIndex('client_credentials', 'client_id');
        SafeSchema::addIndex('dscs', 'client_id');
        SafeSchema::addIndex('dscs', 'expiry_date');
        SafeSchema::addIndex('tds_entries', 'invoice_id');
        SafeSchema::addIndex('subscriptions', 'client_id');
        SafeSchema::addIndex('subscriptions', 'status');
    }

    public function down(): void
    {
        // Optional performance indexes; safe to leave in place on rollback.
    }
};

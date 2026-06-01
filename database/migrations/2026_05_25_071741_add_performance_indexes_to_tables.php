<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clients Table Indexes
        Schema::table('clients', function (Blueprint $table) {
            $table->index('manager_id');
            $table->index('branch_id');
            $table->index('status');
            $table->index('category');
        });

        // 2. Tasks Table Indexes
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('client_id');
            $table->index('assigned_to');
            $table->index('created_by');
            $table->index('status');
            $table->index('due_date');
            if (Schema::hasColumn('tasks', 'invoice_id')) {
                $table->index('invoice_id');
            }
        });

        // 3. Invoices Table Indexes
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('client_id');
            $table->index('branch_id');
            $table->index('status');
        });

        // 4. Payments Table Indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('invoice_id');
            $table->index('status');
        });

        // 5. Users Table Indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('branch_id');
            $table->index('role');
        });

        // 6. Client Credentials Table Indexes
        if (Schema::hasTable('client_credentials')) {
            Schema::table('client_credentials', function (Blueprint $table) {
                $table->index('client_id');
            });
        }

        // 7. DSCs Table Indexes
        if (Schema::hasTable('dscs')) {
            Schema::table('dscs', function (Blueprint $table) {
                $table->index('client_id');
                $table->index('expiry_date');
            });
        }

        // 8. TDS Entries Table Indexes
        if (Schema::hasTable('tds_entries')) {
            Schema::table('tds_entries', function (Blueprint $table) {
                $table->index('invoice_id');
            });
        }

        // 9. Subscriptions Table Indexes
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->index('client_id');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Clients Table Indexes
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['manager_id']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['category']);
        });

        // 2. Tasks Table Indexes
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['due_date']);
            if (Schema::hasColumn('tasks', 'invoice_id')) {
                $table->dropIndex(['invoice_id']);
            }
        });

        // 3. Invoices Table Indexes
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['status']);
        });

        // 4. Payments Table Indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['status']);
        });

        // 5. Users Table Indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['role']);
        });

        // 6. Client Credentials Table Indexes
        if (Schema::hasTable('client_credentials')) {
            Schema::table('client_credentials', function (Blueprint $table) {
                $table->dropIndex(['client_id']);
            });
        }

        // 7. DSCs Table Indexes
        if (Schema::hasTable('dscs')) {
            Schema::table('dscs', function (Blueprint $table) {
                $table->dropIndex(['client_id']);
                $table->dropIndex(['expiry_date']);
            });
        }

        // 8. TDS Entries Table Indexes
        if (Schema::hasTable('tds_entries')) {
            Schema::table('tds_entries', function (Blueprint $table) {
                $table->dropIndex(['invoice_id']);
            });
        }

        // 9. Subscriptions Table Indexes
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropIndex(['client_id']);
                $table->dropIndex(['status']);
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_dues')) {
            return;
        }

        if (! Schema::hasColumn('service_dues', 'billing_status')) {
            Schema::table('service_dues', function (Blueprint $table) {
                $table->enum('billing_status', ['Pending', 'Unbilled', 'Billed', 'Non-Billable'])->default('Pending')->after('status');
            });
        }

        if (! Schema::hasColumn('service_dues', 'billing_amount')) {
            Schema::table('service_dues', function (Blueprint $table) {
                $table->decimal('billing_amount', 10, 2)->nullable()->after('billing_status');
            });
        }

        if (! Schema::hasColumn('service_dues', 'invoice_id')) {
            Schema::table('service_dues', function (Blueprint $table) {
                $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null')->after('billing_amount');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_dues')) {
            return;
        }

        Schema::table('service_dues', function (Blueprint $table) {
            if (Schema::hasColumn('service_dues', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
            if (Schema::hasColumn('service_dues', 'billing_amount')) {
                $table->dropColumn('billing_amount');
            }
            if (Schema::hasColumn('service_dues', 'billing_status')) {
                $table->dropColumn('billing_status');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('invoice_number');
            }
            if (! Schema::hasColumn('invoices', 'work_period')) {
                $table->string('work_period')->nullable()->after('reference_number');
            }
            if (! Schema::hasColumn('invoices', 'project_name')) {
                $table->string('project_name')->nullable()->after('work_period');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columns = ['reference_number', 'work_period', 'project_name'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

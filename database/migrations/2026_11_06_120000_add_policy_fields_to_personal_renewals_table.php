<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * LIC & insurance reminders: more categories (vehicle, term, home insurance)
 * and policy details + per-policy reminder settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_renewals', function (Blueprint $table) {
            // Was ENUM(LIC, Loan, Medical, Other); now free string for new insurance types.
            $table->string('category', 30)->default('Other')->change();
        });

        Schema::table('personal_renewals', function (Blueprint $table) {
            if (! Schema::hasColumn('personal_renewals', 'policy_number')) {
                $table->string('policy_number', 100)->nullable();
            }
            if (! Schema::hasColumn('personal_renewals', 'insurer')) {
                $table->string('insurer', 150)->nullable();
            }
            if (! Schema::hasColumn('personal_renewals', 'insured_person')) {
                $table->string('insured_person', 150)->nullable();
            }
            if (! Schema::hasColumn('personal_renewals', 'sum_assured')) {
                $table->decimal('sum_assured', 14, 2)->nullable();
            }
            if (! Schema::hasColumn('personal_renewals', 'maturity_date')) {
                $table->date('maturity_date')->nullable();
            }
            if (! Schema::hasColumn('personal_renewals', 'remind_days_before')) {
                $table->unsignedSmallInteger('remind_days_before')->default(7);
            }
            if (! Schema::hasColumn('personal_renewals', 'whatsapp_reminder')) {
                $table->boolean('whatsapp_reminder')->default(true);
            }
            if (! Schema::hasColumn('personal_renewals', 'last_reminded_on')) {
                $table->date('last_reminded_on')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_renewals', function (Blueprint $table) {
            foreach (['policy_number', 'insurer', 'insured_person', 'sum_assured', 'maturity_date', 'remind_days_before', 'whatsapp_reminder', 'last_reminded_on'] as $column) {
                if (Schema::hasColumn('personal_renewals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

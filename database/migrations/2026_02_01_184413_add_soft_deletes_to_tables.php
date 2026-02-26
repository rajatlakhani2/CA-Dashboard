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
        if (!Schema::hasColumn('clients', 'deleted_at')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        if (!Schema::hasColumn('tasks', 'deleted_at')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        if (!Schema::hasColumn('invoices', 'deleted_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        if (!Schema::hasColumn('service_dues', 'deleted_at')) {
            Schema::table('service_dues', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('service_dues', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

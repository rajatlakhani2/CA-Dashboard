<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_dues')) {
            Schema::table('service_dues', function (Blueprint $table) {
                $table->index('client_service_id');
                $table->index('due_date');
                $table->index('status');
            });
        }

        if (Schema::hasTable('client_credentials') && Schema::hasColumn('client_credentials', 'category')) {
            Schema::table('client_credentials', function (Blueprint $table) {
                $table->index('category');
            });
        }

        if (Schema::hasTable('service_document_requirements')) {
            Schema::table('service_document_requirements', function (Blueprint $table) {
                $table->index('service_id');
            });
        }

        if (Schema::hasTable('client_service_document_checks')) {
            Schema::table('client_service_document_checks', function (Blueprint $table) {
                $table->index('client_service_id');
            });
        }

        if (Schema::hasTable('time_entries')) {
            Schema::table('time_entries', function (Blueprint $table) {
                $table->index('task_id');
                $table->index('user_id');
                $table->index('date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('service_dues')) {
            Schema::table('service_dues', function (Blueprint $table) {
                $table->dropIndex(['client_service_id']);
                $table->dropIndex(['due_date']);
                $table->dropIndex(['status']);
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'dashboard_preferences')) {
            Schema::table('users', function (Blueprint $table) {
                // e.g. {"inbox_sections": ["kpis", "inbox", "calendar", ...]}
                $table->json('dashboard_preferences')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'dashboard_preferences')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('dashboard_preferences');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'dashboard_layout')) {
            Schema::table('users', function (Blueprint $table) {
                // Null = follow the firm default (Settings → Workspace).
                $table->string('dashboard_layout', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'dashboard_layout')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('dashboard_layout');
            });
        }
    }
};

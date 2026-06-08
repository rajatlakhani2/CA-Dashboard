<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('organizations') && ! Schema::hasColumn('organizations', 'is_demo')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('is_active');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'demo_tour_completed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('demo_tour_completed_at')->nullable()->after('remember_token');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('organizations') && Schema::hasColumn('organizations', 'is_demo')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'demo_tour_completed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('demo_tour_completed_at');
            });
        }
    }
};

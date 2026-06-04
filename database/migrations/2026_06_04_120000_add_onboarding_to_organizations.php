<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('organizations')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            if (! Schema::hasColumn('organizations', 'onboarding_completed_at')) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('organizations') && Schema::hasColumn('organizations', 'onboarding_completed_at')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropColumn('onboarding_completed_at');
            });
        }
    }
};

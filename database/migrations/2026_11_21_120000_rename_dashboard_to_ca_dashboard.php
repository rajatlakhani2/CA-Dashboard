<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The product is now called "CA Dashboard". Only the old default name is replaced; custom names stay. */
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('key', 'dashboard_name')
            ->whereIn(DB::raw('LOWER(TRIM(value))'), ['vouchex', 'vouchex ca', 'vouchex dashboard'])
            ->update(['value' => 'CA Dashboard']);

        DB::table('settings')
            ->where('key', 'dashboard_tagline')
            ->where('value', 'Multi-firm workspace')
            ->update(['value' => 'Practice management for CA firms']);
    }

    public function down(): void
    {
        // Name changes are not reversed.
    }
};

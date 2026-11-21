<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Auto-logout after inactivity is now always on, default 5 minutes (like VouchEx).
 * Firms that had it switched off ("0") move to 5; other chosen values above 120 are capped.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->where('key', 'auto_logout_minutes')
            ->whereIn('value', ['0', ''])
            ->update(['value' => '5']);
        DB::table('settings')->where('key', 'auto_logout_minutes')
            ->whereIn('value', ['240', '480'])
            ->update(['value' => '120']);
    }

    public function down(): void
    {
        // nothing to undo
    }
};

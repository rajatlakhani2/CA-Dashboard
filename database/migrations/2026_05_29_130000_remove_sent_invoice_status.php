<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoices')
            ->where('status', 'Sent')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'Overdue']);

        DB::table('invoices')
            ->where('status', 'Sent')
            ->update(['status' => 'Draft']);
    }

    public function down(): void
    {
        // Sent status is no longer used; no automatic rollback.
    }
};

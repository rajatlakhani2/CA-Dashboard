<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->safeIndex('service_dues', 'client_service_id');
        $this->safeIndex('service_dues', 'due_date');
        $this->safeIndex('service_dues', 'status');
        $this->safeIndex('client_credentials', 'category');
        $this->safeIndex('service_document_requirements', 'service_id');
        $this->safeIndex('client_service_document_checks', 'client_service_id');
        $this->safeIndex('time_entries', 'task_id');
        $this->safeIndex('time_entries', 'user_id');
        $this->safeIndex('time_entries', 'date');
    }

    public function down(): void
    {
        // Indexes are optional performance helpers; no down required for production safety.
    }

    private function safeIndex(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $indexName = "{$table}_{$column}_index";

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->index($column);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$table, $indexName]
        );

        return count($rows) > 0;
    }
};

<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Helpers for production databases that were partially migrated by hand or older deploys.
 */
class SafeSchema
{
    public static function hasIndex(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('{$table}')");

            foreach ($rows as $row) {
                if (($row->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$table, $indexName]
        );

        return count($rows) > 0;
    }

    public static function addIndex(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $indexName = "{$table}_{$column}_index";

        if (self::hasIndex($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->index($column);
        });
    }

    public static function hasForeignKey(string $table, string $constraintName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return false;
        }

        $rows = DB::select(
            'SELECT 1 FROM information_schema.table_constraints
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND constraint_name = ?
               AND constraint_type = \'FOREIGN KEY\'
             LIMIT 1',
            [$table, $constraintName]
        );

        return count($rows) > 0;
    }

    /**
     * Add a foreign key with a short constraint name (MySQL max 64 chars).
     */
    public static function addForeignKey(
        string $table,
        string $column,
        string $referencedTable,
        string $constraintName,
        string $onDelete = 'restrict'
    ): void {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        if (! Schema::hasTable($referencedTable)) {
            return;
        }

        if (self::hasForeignKey($table, $constraintName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $constraintName, $onDelete) {
            $foreign = $blueprint->foreign($column, $constraintName)
                ->references('id')
                ->on($referencedTable);

            match ($onDelete) {
                'cascade' => $foreign->cascadeOnDelete(),
                'set null' => $foreign->nullOnDelete(),
                default => $foreign,
            };
        });
    }
}

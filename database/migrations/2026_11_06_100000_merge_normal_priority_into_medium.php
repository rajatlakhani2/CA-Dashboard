<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tasks now use three priorities: High, Medium, Low.
 * Existing "Normal" tasks/templates become "Medium".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tasks')) {
            DB::table('tasks')->where('priority', 'Normal')->update(['priority' => 'Medium']);

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE tasks MODIFY priority ENUM('High','Medium','Normal','Low') NOT NULL DEFAULT 'Medium'");
            }
        }

        if (Schema::hasTable('task_templates') && Schema::hasColumn('task_templates', 'priority')) {
            DB::table('task_templates')->where('priority', 'Normal')->update(['priority' => 'Medium']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tasks MODIFY priority ENUM('High','Medium','Normal','Low') NOT NULL DEFAULT 'Normal'");
        }
    }
};

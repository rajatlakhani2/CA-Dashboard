<?php

use App\Models\Task;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Task priority is now High / Medium / Low, set automatically from the due date unless chosen by hand.
 * - Existing High / Medium tasks were chosen by someone: kept as they are (manual).
 * - Existing Normal / Low tasks become "Auto" and get the priority their due date gives today.
 * - "Normal" is retired (closed tasks that still say Normal become Low).
 * - Service templates left on the old default (Medium / Normal) become Auto, so GST and other
 *   recurring work follows the due-date rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tasks', 'priority_auto')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->boolean('priority_auto')->default(true)->after('priority');
            });
        }

        DB::table('tasks')->whereIn('priority', ['High', 'Medium'])->update(['priority_auto' => false]);
        DB::table('tasks')->where('priority', 'Normal')->update(['priority' => 'Low']);
        DB::table('tasks')->where('priority', 'Low')->update(['priority_auto' => true]);

        if (Schema::hasTable('task_templates')) {
            DB::table('task_templates')->whereIn('priority', ['Medium', 'Normal'])->update(['priority' => 'Auto']);
            Schema::table('task_templates', function (Blueprint $table) {
                $table->string('priority')->default('Auto')->change();
            });
        }

        Task::refreshAutoPriorities();
    }

    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'priority_auto')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('priority_auto');
            });
        }
    }
};

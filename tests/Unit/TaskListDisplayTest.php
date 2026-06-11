<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Support\TaskListDisplay;
use Tests\TestCase;

class TaskListDisplayTest extends TestCase
{
    public function test_progress_percent_for_in_progress(): void
    {
        $task = new Task(['status' => Task::STATUS_IN_PROGRESS]);

        $this->assertSame(75, TaskListDisplay::progressPercent($task));
    }

    public function test_due_context_label_for_today(): void
    {
        $task = new Task(['due_date' => now()]);

        $this->assertSame('Due Today', TaskListDisplay::dueContextLabel($task));
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Request;

class DashboardCalendarFilters
{
    public function __construct(
        public bool $showTasks = true,
        public bool $showDues = true,
        public ?int $serviceId = null,
        public ?int $assignedTo = null,
        public ?int $branchId = null,
        public ?string $category = null,
        public ?string $dueStatus = 'active',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $status = $request->input('due_status', 'active');
        if (! in_array($status, ['active', 'pending', 'overdue', 'completed', 'all'], true)) {
            $status = 'active';
        }

        $category = $request->input('category');
        if ($category !== null && ! in_array(strtoupper($category), ['A', 'B', 'C'], true)) {
            $category = null;
        }

        return new self(
            showTasks: $request->boolean('show_tasks', true),
            showDues: $request->boolean('show_dues', true),
            serviceId: $request->filled('service_id') ? (int) $request->service_id : null,
            assignedTo: $request->filled('assigned_to') ? (int) $request->assigned_to : null,
            branchId: $request->filled('branch_id') ? (int) $request->branch_id : null,
            category: $category ? strtoupper($category) : null,
            dueStatus: $status,
        );
    }

    public function toQueryArray(): array
    {
        return array_filter([
            'show_tasks' => $this->showTasks ? '1' : '0',
            'show_dues' => $this->showDues ? '1' : '0',
            'service_id' => $this->serviceId,
            'assigned_to' => $this->assignedTo,
            'branch_id' => $this->branchId,
            'category' => $this->category,
            'due_status' => $this->dueStatus,
        ], fn ($v) => $v !== null && $v !== '');
    }
}

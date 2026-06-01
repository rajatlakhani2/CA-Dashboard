<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:High,Medium,Normal,Low',
            'status' => 'required|in:' . implode(',', Task::STATUSES),
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('priority') === 'Normal') {
            $this->merge(['priority' => 'Medium']);
        }
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:High,Medium,Low',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
        ];
    }
}

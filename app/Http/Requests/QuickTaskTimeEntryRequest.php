<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickTaskTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hours' => 'required|numeric|min:0.25|max:24',
            'date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'is_billable' => 'nullable|boolean',
        ];
    }
}

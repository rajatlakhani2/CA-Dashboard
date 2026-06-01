<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'group_name' => 'nullable|string|max:255',
            'entity_type' => 'nullable|string',
            'industry' => 'nullable|string',
            'pan' => 'required|string|unique:clients,pan',
            'gstin' => 'nullable|string|unique:clients,gstin',
            'cin' => 'nullable|string',
            'tan' => 'nullable|string',
            'primary_contact_name' => 'nullable|string',
            'primary_contact_phone' => 'nullable|string',
            'primary_contact_email' => 'nullable|email',
            'category' => 'required|in:A,B,C',
            'status' => 'required|in:' . implode(',', [Client::STATUS_ACTIVE, Client::STATUS_ON_HOLD, Client::STATUS_CLOSED]),
            'tags' => 'nullable|string',
            'billing_cycle' => 'nullable|string',
            'registered_address' => 'nullable|string',
            'services' => 'array',
            'custom_due_days' => 'array',
        ];
    }
}

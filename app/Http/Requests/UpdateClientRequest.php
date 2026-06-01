<?php

namespace App\Http\Requests;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'name' => 'required|string|max:255',
            'group_name' => 'nullable|string|max:255',
            'pan' => 'required|string|unique:clients,pan,' . $client->id,
            'gstin' => 'nullable|string|unique:clients,gstin,' . $client->id,
            'category' => 'required|in:A,B,C',
            'status' => 'required|in:' . implode(',', [Client::STATUS_ACTIVE, Client::STATUS_ON_HOLD, Client::STATUS_CLOSED]),
            'tags' => 'nullable|string',
            'entity_type' => 'nullable|string',
            'industry' => 'nullable|string',
            'billing_cycle' => 'nullable|string',
            'primary_contact_name' => 'nullable|string',
            'primary_contact_phone' => 'nullable|string',
            'primary_contact_email' => 'nullable|email',
            'registered_address' => 'nullable|string',
            'services' => 'array',
            'custom_due_days' => 'array',
        ];
    }
}

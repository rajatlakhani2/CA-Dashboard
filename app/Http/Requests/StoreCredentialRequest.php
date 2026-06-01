<?php

namespace App\Http\Requests;

use App\Models\ClientCredential;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'portal_name' => 'required|string|max:255',
            'category' => ['nullable', 'string', Rule::in(ClientCredential::CATEGORIES)],
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}

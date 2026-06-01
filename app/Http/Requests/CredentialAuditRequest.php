<?php

namespace App\Http\Requests;

use App\Models\ClientCredential;
use Illuminate\Foundation\Http\FormRequest;

class CredentialAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:' . implode(',', [
                ClientCredential::AUDIT_REVEALED_PASSWORD,
                ClientCredential::AUDIT_REVEALED_USERNAME,
                ClientCredential::AUDIT_COPIED_PASSWORD,
                ClientCredential::AUDIT_COPIED_USERNAME,
            ]),
        ];
    }
}

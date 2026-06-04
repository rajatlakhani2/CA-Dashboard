<?php

namespace App\Http\Requests;

use App\Support\OrganizationContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(
                    fn ($q) => $q->where('organization_id', OrganizationContext::id() ?? $this->user()?->organization_id)
                ),
            ],
            'mobile' => 'nullable|string|max:20',
            'role' => 'required|in:partner,manager,staff,intern',
            'branch_id' => 'nullable|exists:branches,id',
            'password' => 'required|string|min:8',
        ];
    }
}

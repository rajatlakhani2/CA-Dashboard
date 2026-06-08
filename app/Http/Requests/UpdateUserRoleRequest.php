<?php

namespace App\Http\Requests;

use App\Support\WorkspaceProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(array_keys(WorkspaceProfile::roles()))],
            'mobile' => 'required|string|max:20',
        ];
    }
}

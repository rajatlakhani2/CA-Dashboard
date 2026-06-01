<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'nullable|string|max:20',
            'role' => 'required|in:partner,manager,staff,intern',
            'branch_id' => 'nullable|exists:branches,id',
            'password' => 'required|string|min:8',
        ];
    }
}

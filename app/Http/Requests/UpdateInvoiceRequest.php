<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesInvoicePayload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    use ValidatesInvoicePayload;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->invoiceRules($this->route('invoice')->id, includeStatus: true);
    }
}

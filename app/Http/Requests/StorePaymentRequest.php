<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => 'required|exists:invoices,id',
            'receipt_number' => 'required|unique:payments,receipt_number',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_mode' => 'required|in:Cash,Cheque,UPI,Bank Transfer,Online',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ];
    }
}

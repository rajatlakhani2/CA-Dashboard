<?php

namespace App\Http\Requests\Concerns;

use App\Models\Invoice;

trait ValidatesInvoicePayload
{
    protected function invoiceRules(?int $invoiceId = null, bool $includeStatus = false): array
    {
        $numberRule = 'required|unique:invoices,invoice_number';
        if ($invoiceId) {
            $numberRule .= ',' . $invoiceId;
        }

        $rules = [
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => $numberRule,
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'place_of_supply' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
            'work_period' => 'nullable|string|max:100',
            'project_name' => 'nullable|string|max:255',
            'reverse_charge' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.hsn_sac_code' => 'nullable|string',
            'items.*.gst_rate' => 'nullable|numeric|min:0|max:28',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
        ];

        if ($includeStatus) {
            $rules['status'] = 'required|in:' . implode(',', array_diff(Invoice::STATUSES, [Invoice::STATUS_CANCELLED]));
        }

        return $rules;
    }
}

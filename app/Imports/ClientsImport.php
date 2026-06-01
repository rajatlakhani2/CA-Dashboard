<?php

namespace App\Imports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ClientsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError
{
    use SkipsFailures, SkipsErrors;

    private $nextId;

    public function __construct(private ?int $branchId = null)
    {
        $lastClient = Client::latest('id')->first();
        $this->nextId = $lastClient ? $lastClient->id + 1 : 1;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Simple mapping check
        if (empty($row['name']) || empty($row['pan'])) {
            return null;
        }

        // Auto-generate client code if not provided
        $clientCode = !empty($row['client_code'])
            ? $row['client_code']
            : $this->generateClientCode();

        return new Client([
            'client_code' => $clientCode,
            'name' => $row['name'],
            'entity_type' => !empty($row['entity_type']) ? $row['entity_type'] : null,
            'industry' => !empty($row['industry']) ? $row['industry'] : null,
            'pan' => strtoupper($row['pan']),
            'gstin' => !empty($row['gstin']) ? strtoupper($row['gstin']) : null,
            'cin' => !empty($row['cin']) ? strtoupper($row['cin']) : null,
            'tan' => !empty($row['tan']) ? strtoupper($row['tan']) : null,
            'registered_address' => !empty($row['registered_address']) ? $row['registered_address'] : null,
            'status' => !empty($row['status']) ? $row['status'] : Client::STATUS_ACTIVE,
            'category' => !empty($row['category']) ? $row['category'] : 'C',
            'primary_contact_name' => !empty($row['primary_contact_name']) ? $row['primary_contact_name'] : null,
            'primary_contact_phone' => !empty($row['phone']) ? strval($row['phone']) : null,
            'primary_contact_email' => !empty($row['email']) ? $row['email'] : null,
            'gst_applicable' => !empty($row['gstin']) ? true : false,
            'branch_id' => $this->branchId,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'pan' => ['required', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i', 'unique:clients,pan'],
            'email' => 'nullable|email',
            'category' => ['nullable', Rule::in(['A', 'B', 'C'])],
        ];
    }

    private function generateClientCode()
    {
        $code = 'CL-' . str_pad($this->nextId, 4, '0', STR_PAD_LEFT);
        $this->nextId++;
        return $code;
    }
}

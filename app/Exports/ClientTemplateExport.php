<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClientTemplateExport implements WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'client_code',
            'name',
            'entity_type',
            'industry',
            'pan',
            'gstin',
            'cin',
            'tan',
            'registered_address',
            'status',
            'category',
            'primary_contact_name',
            'phone',
            'email',
        ];
    }

    public function title(): string
    {
        return 'Client Import Template';
    }
}

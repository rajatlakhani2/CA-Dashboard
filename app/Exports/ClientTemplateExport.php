<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClientTemplateExport implements FromArray, WithTitle
{
    public function array(): array
    {
        return [
            [
                'client_code',
                'name',
                'group_name',
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
                'services',
            ],
            [
                '',
                'Example Client Ltd',
                'Nileshbhai',
                'Private Limited',
                'Trading',
                'ABCDE1234F',
                '27ABCDE1234F1Z5',
                '',
                '',
                '123 MG Road, Mumbai',
                'Active',
                'A',
                'Contact Person',
                '9876543210',
                'client@example.com',
                'IT Return, GST Return',
            ],
        ];
    }

    public function title(): string
    {
        return 'Client Import Template';
    }
}

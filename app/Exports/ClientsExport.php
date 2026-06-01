<?php

namespace App\Exports;

use App\Models\Client;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private ?User $user = null)
    {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Client::query();

        if ($this->user) {
            $query->visibleTo($this->user);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Client Code',
            'Name',
            'Entity Type',
            'Industry',
            'PAN',
            'GSTIN',
            'CIN',
            'TAN',
            'Registered Address',
            'Status',
            'Category',
            'Primary Contact Name',
            'Manager',
            'Phone',
            'Email'
        ];
    }

    public function map($client): array
    {
        return [
            $client->client_code,
            $client->name,
            $client->entity_type,
            $client->industry,
            $client->pan,
            $client->gstin,
            $client->cin,
            $client->tan,
            $client->registered_address,
            $client->status,
            $client->category,
            $client->primary_contact_name,
            $client->manager ? $client->manager->name : '',
            $client->primary_contact_phone,
            $client->primary_contact_email,
        ];
    }
}

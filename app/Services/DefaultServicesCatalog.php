<?php

namespace App\Services;

use App\Models\Service;

class DefaultServicesCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'IT Return',
                'code' => 'ITR',
                'description' => 'Income Tax Return Filing',
                'frequency' => 'Annually',
                'due_day' => 31,
                'due_month' => 7,
                'is_statutory' => true,
            ],
            [
                'name' => 'Tax Audit',
                'code' => 'TAX_AUDIT',
                'description' => 'Tax Audit under Income Tax Act',
                'frequency' => 'Annually',
                'due_day' => 30,
                'due_month' => 9,
                'is_statutory' => true,
            ],
            [
                'name' => 'Statutory Audit',
                'code' => 'STAT_AUDIT',
                'description' => 'Company Statutory Audit',
                'frequency' => 'Annually',
                'due_day' => 30,
                'due_month' => 9,
                'is_statutory' => true,
            ],
            [
                'name' => 'GST Return',
                'code' => 'GST',
                'description' => 'Monthly GST Return (GSTR-3B)',
                'frequency' => 'Monthly',
                'due_day' => 20,
                'due_month' => null,
                'is_statutory' => true,
            ],
            [
                'name' => 'GSTR-1 (Monthly)',
                'code' => 'GSTR1-M',
                'description' => 'Monthly GSTR-1 Return',
                'frequency' => 'Monthly',
                'due_day' => 11,
                'due_month' => null,
                'is_statutory' => true,
            ],
            [
                'name' => 'Other Services',
                'code' => 'OTHER',
                'description' => 'Consultancy and other ad-hoc services',
                'frequency' => 'One-Time',
                'due_day' => null,
                'due_month' => null,
                'is_statutory' => false,
            ],
        ];
    }

    public static function ensureExists(): void
    {
        foreach (self::definitions() as $svc) {
            Service::updateOrCreate(['code' => $svc['code']], $svc);
        }
    }
}

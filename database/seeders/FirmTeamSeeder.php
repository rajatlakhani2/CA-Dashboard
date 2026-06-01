<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FirmTeamSeeder extends Seeder
{
    public function run(): void
    {
        $this->upgradeLegacyRajatAccount();

        $accounts = [
            [
                'name' => 'Rajat Lakhani',
                'email' => 'rajat@rlassociates.in',
                'role' => 'partner',
                'mobile' => '919999000001',
            ],
            [
                'name' => 'Nilesh Bhai',
                'email' => 'nilesh@rlassociates.in',
                'role' => 'associate',
                'mobile' => '919999000002',
            ],
            [
                'name' => 'Article Clerk',
                'email' => 'article@rlassociates.in',
                'role' => 'article',
                'mobile' => '919999000003',
            ],
        ];

        foreach ($accounts as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => $data['role'],
                    'mobile' => $data['mobile'],
                    'password' => Hash::make('password'),
                    'module_access' => \App\Support\ModuleAccess::defaultsForRole($data['role']),
                ]
            );
        }
    }

    /**
     * Fix an existing Rajat row that was created as staff before firm roles were seeded.
     */
    private function upgradeLegacyRajatAccount(): void
    {
        $legacy = User::query()
            ->where(function ($query) {
                $query->where('email', 'rajat@rlassociates.in')
                    ->orWhere('email', 'rajat@rla.local')
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%rajat%']);
            })
            ->orderBy('id')
            ->first();

        if (! $legacy) {
            return;
        }

        $legacy->forceFill([
            'name' => 'Rajat Lakhani',
            'email' => 'rajat@rlassociates.in',
            'role' => 'partner',
        ])->save();
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\User;
use Illuminate\Console\Command;

class AssignClientPortfolios extends Command
{
    protected $signature = 'clients:assign-portfolios {--dry-run : Show changes without saving}';

    protected $description = 'Assign manager_id so Rajat and Nilesh Bhai each own their client portfolios';

    public function handle(): int
    {
        $rajat = User::query()
            ->where(function ($query) {
                $query->where('email', 'rajat@rla.local')
                    ->orWhere('name', 'like', '%Rajat%');
            })
            ->where('role', 'partner')
            ->first();

        $nilesh = User::query()
            ->where(function ($query) {
                $query->where('email', 'nilesh@rla.local')
                    ->orWhere('name', 'like', '%Nilesh%');
            })
            ->where('role', 'associate')
            ->first();

        if (! $rajat) {
            $this->error('Rajat (partner) user not found. Run: php artisan db:seed --class=FirmTeamSeeder');

            return self::FAILURE;
        }

        if (! $nilesh) {
            $this->error('Nilesh Bhai (associate) user not found. Run: php artisan db:seed --class=FirmTeamSeeder');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $rajatCount = 0;
        $nileshCount = 0;

        Client::query()->orderBy('id')->chunkById(200, function ($clients) use ($rajat, $nilesh, $dryRun, &$rajatCount, &$nileshCount) {
            foreach ($clients as $client) {
                $owner = $this->resolvePortfolioOwner($client);

                if ($owner === 'nilesh') {
                    $nileshCount++;
                    if (! $dryRun) {
                        $client->update(['manager_id' => $nilesh->id]);
                    }
                    continue;
                }

                $rajatCount++;
                if (! $dryRun) {
                    $client->update(['manager_id' => $rajat->id]);
                }
            }
        });

        $this->info('Portfolio assignment ' . ($dryRun ? '(dry run) ' : '') . 'complete.');
        $this->line("Rajat clients: {$rajatCount}");
        $this->line("Nilesh Bhai clients: {$nileshCount}");

        return self::SUCCESS;
    }

    private function resolvePortfolioOwner(Client $client): string
    {
        $group = strtolower((string) $client->group_name);
        $tags = json_encode($client->tags ?? []);
        $haystack = strtolower($group . ' ' . $tags);

        if (str_contains($haystack, 'nilesh')) {
            return 'nilesh';
        }

        return 'rajat';
    }
}

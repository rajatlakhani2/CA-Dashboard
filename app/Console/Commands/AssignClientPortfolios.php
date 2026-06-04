<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\User;
use Illuminate\Console\Command;

class AssignClientPortfolios extends Command
{
    protected $signature = 'clients:assign-portfolios {--dry-run : Show changes without saving}';

    protected $description = 'Assign manager_id to partner vs associate portfolios by client reference/tags';

    public function handle(): int
    {
        $partner = User::query()
            ->where('role', 'partner')
            ->orderBy('id')
            ->first();

        $associate = User::query()
            ->where('role', 'associate')
            ->orderBy('id')
            ->first();

        if (! $partner) {
            $this->error('Partner user not found. Run: php artisan users:ensure-firm-logins');

            return self::FAILURE;
        }

        if (! $associate) {
            $this->warn('No associate user — all clients will be assigned to the partner.');

            $associate = $partner;
        }

        $dryRun = (bool) $this->option('dry-run');
        $partnerCount = 0;
        $associateCount = 0;

        Client::query()->orderBy('id')->chunkById(200, function ($clients) use ($partner, $associate, $dryRun, &$partnerCount, &$associateCount) {
            foreach ($clients as $client) {
                $owner = $this->resolvePortfolioOwner($client);

                if ($owner === 'associate') {
                    $associateCount++;
                    if (! $dryRun) {
                        $client->update(['manager_id' => $associate->id]);
                    }
                    continue;
                }

                $partnerCount++;
                if (! $dryRun) {
                    $client->update(['manager_id' => $partner->id]);
                }
            }
        });

        $this->info('Portfolio assignment ' . ($dryRun ? '(dry run) ' : '') . 'complete.');
        $this->line("Partner portfolio: {$partnerCount}");
        $this->line("Associate portfolio: {$associateCount}");

        return self::SUCCESS;
    }

    private function resolvePortfolioOwner(Client $client): string
    {
        $group = strtolower((string) $client->group_name);
        $tags = strtolower(json_encode($client->tags ?? []));
        $haystack = $group . ' ' . $tags;

        if (str_contains($haystack, 'associate') || str_contains($haystack, 'portfolio-b')) {
            return 'associate';
        }

        return 'partner';
    }
}

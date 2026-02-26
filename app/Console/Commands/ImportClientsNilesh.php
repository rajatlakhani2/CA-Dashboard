<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Client;
use Illuminate\Support\Str;

class ImportClientsNilesh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clients-nilesh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import clients from D:\New folder\Rajat\Rajat\IT Return\Nileshbhai';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = 'D:\New folder\Rajat\Rajat\IT Return\Nileshbhai';

        if (!File::exists($path)) {
            $this->error("Directory not found: $path");
            return 1;
        }

        $directories = File::directories($path);
        $this->info("Found " . count($directories) . " directories to process.");

        $bar = $this->output->createProgressBar(count($directories));
        $bar->start();

        $c = 0;
        $s = 0;

        foreach ($directories as $dir) {
            $folderName = basename($dir);
            $clientName = trim($folderName);

            // Skip garbage folders
            if (in_array($clientName, ['OLD', 'Extra', 'New folder', 'New folder (2)', 'New folder (3)'])) {
                $bar->advance();
                continue;
            }

            // Check if exists
            $exists = Client::where('name', $clientName)->exists();
            if ($exists) {
                $s++;
                $bar->advance();
                continue;
            }

            // Look for PAN inside the folder (naive search in filenames)
            $pan = null;
            $files = File::allFiles($dir);
            foreach ($files as $file) {
                // Regex for PAN in filename
                if (preg_match('/[A-Z]{5}[0-9]{4}[A-Z]{1}/', $file->getFilename(), $matches)) {
                    $pan = $matches[0];
                    break;
                }
            }

            try {
                Client::create([
                    'name' => $clientName,
                    'client_code' => 'CLI-' . strtoupper(Str::random(5)),
                    'pan' => $pan,
                    'tags' => ['Nileshbhai client'],
                    'status' => 'Active',
                    'category' => 'C',
                ]);
                $c++;
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->errorInfo[1] == 19) { // SQLite constraint violation code
                    $this->error("\nDuplicate PAN found for $clientName ($pan). Skipping.");
                } else {
                    $this->error("\nFailed to create $clientName: " . $e->getMessage());
                }
            } catch (\Exception $e) {
                $this->error("\nFailed to create $clientName: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Import Completed!");
        $this->info("Created: $c clients");
        $this->info("Skipped: $s existing clients");

        return 0;
    }
}

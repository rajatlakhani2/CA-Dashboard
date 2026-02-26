<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ServiceDueGenerator;

class GenerateServiceDues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:generate-dues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate upcoming service dues for all active clients';

    /**
     * Execute the console command.
     */
    public function handle(ServiceDueGenerator $generator)
    {
        $this->info('Starting service due generation...');
        $count = $generator->generateAll();
        $this->info("Successfully generated {$count} new service dues.");
    }
}

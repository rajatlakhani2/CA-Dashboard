<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;

class ListServices extends Command
{
    protected $signature = 'list:services';
    protected $description = 'List all services';

    public function handle()
    {
        $services = Service::all(['id', 'name']);
        foreach ($services as $service) {
            $this->line("ID: {$service->id} | Name: {$service->name}");
        }
    }
}

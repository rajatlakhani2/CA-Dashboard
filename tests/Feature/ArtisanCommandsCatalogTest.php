<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use App\Models\Task;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class ArtisanCommandsCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_service_dues_command_runs(): void
    {
        $service = Service::create([
            'name' => 'Monthly GST',
            'code' => 'MGST',
            'frequency' => 'Monthly',
            'due_day' => 10,
            'is_statutory' => true,
        ]);
        $client = Client::factory()->create();
        ClientService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);

        $this->assertSame(0, Artisan::call('services:generate-dues'));
    }

    public function test_import_clients_folder_fails_on_missing_directory(): void
    {
        $exit = Artisan::call('import:clients-folder', ['--path' => '/nonexistent/folder/path']);
        $this->assertSame(1, $exit);
    }

    public function test_send_daily_task_digest_command(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'mobile' => '919999999999',
            'module_access' => ['tasks' => true],
        ]);
        $client = Client::factory()->create();
        Task::create([
            'title' => 'Due today',
            'client_id' => $client->id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'status' => Task::STATUS_PENDING,
            'priority' => 'Medium',
            'due_date' => now(),
        ]);

        $mock = Mockery::mock(WhatsAppService::class);
        $mock->shouldReceive('sendMessage')->once()->andReturn(['success' => true]);
        $this->app->instance(WhatsAppService::class, $mock);

        $this->assertSame(0, Artisan::call('tasks:send-daily-digest'));
    }

    public function test_backup_command_registered(): void
    {
        $this->assertSame(0, Artisan::call('list', ['--raw' => true]));
        $this->assertStringContainsString('backup:run', Artisan::output());
    }
}

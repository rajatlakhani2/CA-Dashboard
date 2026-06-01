<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the private backups directory exists for tests
        $backupDir = storage_path('app/private/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up any test backups generated during the tests
        $backupDir = storage_path('app/private/backups');
        if (File::exists($backupDir)) {
            $files = File::files($backupDir);
            foreach ($files as $file) {
                if ($file->getExtension() === 'zip' && str_starts_with($file->getFilename(), 'backup-')) {
                    File::delete($file->getRealPath());
                }
            }
        }

        parent::tearDown();
    }

    public function test_backup_command_runs_successfully(): void
    {
        $backupDir = storage_path('app/private/backups');

        // Capture initial backup file count
        $initialCount = count(File::files($backupDir));

        $exitCode = Artisan::call('backup:run');

        $this->assertSame(0, $exitCode);

        $files = File::files($backupDir);
        $this->assertCount($initialCount + 1, $files);

        // Find the newly created backup
        $newBackup = collect($files)->first(function ($file) {
            return $file->getExtension() === 'zip' && str_starts_with($file->getFilename(), 'backup-');
        });

        $this->assertNotNull($newBackup);
    }

    public function test_partner_can_run_download_and_delete_backups(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $backupDir = storage_path('app/private/backups');

        // 1. Partner runs a manual backup
        $response = $this->actingAs($partner)
            ->post(route('system.backup.run'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check if file was created
        $files = File::files($backupDir);
        $newBackup = collect($files)->first(function ($file) {
            return $file->getExtension() === 'zip' && str_starts_with($file->getFilename(), 'backup-');
        });

        $this->assertNotNull($newBackup);
        $filename = $newBackup->getFilename();

        // 2. Partner downloads the backup
        $response = $this->actingAs($partner)
            ->get(route('system.backup.download', ['filename' => $filename]));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename=' . $filename);

        // 3. Partner deletes the backup
        $response = $this->actingAs($partner)
            ->delete(route('system.backup.delete', ['filename' => $filename]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse(File::exists($newBackup->getRealPath()));
    }

    public function test_manager_and_staff_cannot_perform_backup_actions(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $staff = User::factory()->create(['role' => 'staff']);

        // Create a mock backup file
        $backupDir = storage_path('app/private/backups');
        $filename = 'backup-mock-test.zip';
        $filePath = $backupDir . '/' . $filename;
        File::put($filePath, 'mock content');

        // Manager checks
        $this->actingAs($manager)
            ->post(route('system.backup.run'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('system.backup.download', ['filename' => $filename]))
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('system.backup.delete', ['filename' => $filename]))
            ->assertForbidden();

        // Staff checks
        $this->actingAs($staff)
            ->post(route('system.backup.run'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('system.backup.download', ['filename' => $filename]))
            ->assertForbidden();

        $this->actingAs($staff)
            ->delete(route('system.backup.delete', ['filename' => $filename]))
            ->assertForbidden();

        // Verify the file was not deleted
        $this->assertTrue(File::exists($filePath));

        // Clean up
        File::delete($filePath);
    }
}

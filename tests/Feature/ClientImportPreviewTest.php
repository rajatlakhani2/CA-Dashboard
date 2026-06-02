<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Service;
use App\Models\User;
use App\Services\ClientImportApplier;
use App\Services\ClientImportPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ClientImportPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_classifies_create_update_and_invalid(): void
    {
        Client::factory()->create(['pan' => 'EXIST1234A', 'name' => 'Existing Ltd']);

        $csv = "client_code,name,pan,gstin\n,New Co,NEWCO1234B,\n,Bad Row,INVALID,\n,Existing Ltd,EXIST1234A,\n";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);
        $preview = app(ClientImportPreviewService::class)->preview($file);

        $this->assertCount(1, $preview['create']);
        $this->assertSame('NEWCO1234B', $preview['create'][0]['pan']);
        $this->assertCount(1, $preview['update']);
        $this->assertCount(1, $preview['invalid']);
    }

    public function test_preview_flags_duplicate_pan_in_file(): void
    {
        $csv = "name,pan\nFirst,DUPPA1234A\nSecond,DUPPA1234A\n";
        $file = UploadedFile::fake()->createWithContent('dup.csv', $csv);

        $preview = app(ClientImportPreviewService::class)->preview($file);

        $this->assertCount(1, $preview['create']);
        $this->assertCount(1, $preview['invalid']);
        $this->assertStringContainsString('Duplicate PAN', $preview['invalid'][0]['errors'][0]);
    }

    public function test_import_assigns_services_from_column(): void
    {
        Storage::fake('local');

        $itr = Service::create(['name' => 'IT Return', 'code' => 'ITR', 'frequency' => 'Annually']);
        $gst = Service::create(['name' => 'GST Return', 'code' => 'GST', 'frequency' => 'Monthly']);

        $csv = "name,pan,services\nService Client,SERVC1234A,\"Income Tax, GST\"\n";
        $path = 'client-imports/services.csv';
        Storage::put($path, $csv);

        $preview = app(ClientImportPreviewService::class)->preview(Storage::path($path));

        $this->assertCount(1, $preview['create']);
        $this->assertSame([$itr->id, $gst->id], $preview['create'][0]['service_ids']);

        app(ClientImportApplier::class)->apply(Storage::path($path));

        $client = Client::where('pan', 'SERVC1234A')->first();
        $this->assertNotNull($client);
        $this->assertDatabaseHas('client_services', [
            'client_id' => $client->id,
            'service_id' => $itr->id,
            'status' => ClientService::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseHas('client_services', [
            'client_id' => $client->id,
            'service_id' => $gst->id,
        ]);
    }

    public function test_preview_warns_on_unknown_services(): void
    {
        Service::create(['name' => 'IT Return', 'code' => 'ITR', 'frequency' => 'Annually']);

        $csv = "name,pan,services\nWarn Co,WARNC1234A,IT Return; Fake Service\n";
        $file = UploadedFile::fake()->createWithContent('warn.csv', $csv);

        $preview = app(ClientImportPreviewService::class)->preview($file);

        $this->assertCount(1, $preview['create']);
        $this->assertCount(1, $preview['warnings']);
        $this->assertStringContainsString('Fake Service', $preview['warnings'][0]['messages'][0]);
    }

    public function test_confirm_import_applies_create_and_update(): void
    {
        Storage::fake('local');
        $partner = User::factory()->create(['role' => 'partner']);
        $existing = Client::factory()->create(['pan' => 'OLDCL1234A', 'name' => 'Old Name', 'primary_contact_email' => 'old@test.com']);

        $csv = "name,pan,email\nFresh Client,FRESH2345B,new@test.com\nUpdated Name,OLDCL1234A,updated@test.com\n";
        $path = 'client-imports/test.csv';
        Storage::put($path, $csv);

        $result = app(ClientImportApplier::class)->apply(Storage::path($path));

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertDatabaseHas('clients', ['pan' => 'FRESH2345B', 'name' => 'Fresh Client']);
        $this->assertDatabaseHas('clients', ['id' => $existing->id, 'name' => 'Updated Name', 'primary_contact_email' => 'updated@test.com']);
    }

    public function test_preview_http_flow_stores_session_and_confirm(): void
    {
        Storage::fake('local');

        $manager = User::factory()->create(['role' => 'manager']);
        $csv = "name,pan,email\nHttp Client,HTTPA1234A,http@test.com\n";

        $this->actingAs($manager)
            ->post(route('clients.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent('clients.csv', $csv),
            ])
            ->assertOk()
            ->assertSee('Import Preview', false)
            ->assertSee('Http Client', false);

        $stored = session('client_import_file');
        $this->assertNotNull($stored);
        $this->assertTrue(Storage::exists($stored));

        $this->actingAs($manager)
            ->post(route('clients.import.confirm'))
            ->assertRedirect(route('clients.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('clients', ['pan' => 'HTTPA1234A']);
    }
}

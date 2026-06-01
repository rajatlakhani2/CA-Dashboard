<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\PersonalRenewal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PersonalRenewalTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        Storage::fake('public');
    }

    #[Test]
    public function it_can_create_renewal_for_client_with_document()
    {
        $client = Client::factory()->create();
        $file = UploadedFile::fake()->create('policy.pdf', 100);

        $response = $this->post(route('personal-renewals.store'), [
            'client_id' => $client->id,
            'title' => 'LIC Policy',
            'category' => 'LIC',
            'amount' => 5000,
            'due_date' => '2025-05-01',
            'frequency' => 'Yearly',
            'document' => $file,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('personal_renewals', [
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'title' => 'LIC Policy',
            'category' => 'LIC',
            'amount' => 5000,
        ]);

        $renewal = PersonalRenewal::first();
        $this->assertNotNull($renewal->document_path);
        Storage::disk('public')->assertExists($renewal->document_path);
    }

    #[Test]
    public function it_can_delete_renewal_and_file()
    {
        $client = Client::factory()->create();
        $file = UploadedFile::fake()->create('policy.pdf', 100);
        $path = $file->store('personal_renewals', 'public');

        $renewal = PersonalRenewal::create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'title' => 'Old Policy',
            'category' => 'Medical',
            'amount' => 2000,
            'due_date' => '2025-01-01',
            'status' => 'Pending',
            'document_path' => $path,
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->delete(route('personal-renewals.destroy', $renewal));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('personal_renewals', ['id' => $renewal->id]);
        Storage::disk('public')->assertMissing($path);
    }

    #[Test]
    public function it_can_reschedule_own_renewal_from_calendar()
    {
        $renewal = PersonalRenewal::create([
            'user_id' => $this->user->id,
            'title' => 'Calendar Renewal',
            'category' => 'LIC',
            'amount' => 1000,
            'due_date' => '2025-01-01',
            'status' => 'Pending',
        ]);

        $response = $this->postJson(route('calendar.update'), [
            'type' => 'renewal',
            'id' => $renewal->id,
            'new_date' => '2025-01-15',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('personal_renewals', [
            'id' => $renewal->id,
            'due_date' => '2025-01-15 00:00:00',
        ]);
    }

    #[Test]
    public function it_passes_plain_calendar_events_to_the_renewals_view()
    {
        $renewal = PersonalRenewal::create([
            'user_id' => $this->user->id,
            'title' => 'Array Renewal',
            'category' => 'LIC',
            'amount' => 1500,
            'due_date' => '2025-03-01',
            'status' => 'Pending',
        ]);

        $response = $this->get(route('personal-renewals.index'));

        $response->assertOk();

        $events = $response->viewData('events');

        $this->assertIsArray($events);
        $this->assertSame('renewal_' . $renewal->id, $events[0]['id']);
        $this->assertSame($renewal->id, $events[0]['extendedProps']['db_id']);
    }
}

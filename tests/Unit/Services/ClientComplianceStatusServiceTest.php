<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Organization;
use App\Services\ClientComplianceStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientComplianceStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_gst_roc_and_tds_chips(): void
    {
        $org = Organization::create([
            'name' => 'Chip Firm',
            'slug' => 'chip-firm',
            'plan' => 'starter',
            'seat_limit' => 5,
        ]);

        $client = Client::factory()->create([
            'organization_id' => $org->id,
        ]);

        $chips = app(ClientComplianceStatusService::class)->chips($client);

        $this->assertCount(3, $chips);
        $this->assertSame(['gst', 'roc', 'tds'], array_column($chips, 'key'));
    }
}

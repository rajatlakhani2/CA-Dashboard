<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;

class InvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        // Authenticate as a user for all tests
        $this->user = User::factory()->create(['role' => 'manager']);
        $this->actingAs($this->user);
    }

    public function test_invoice_index_page_loads()
    {
        $response = $this->get(route('invoices.index'));
        $response->assertStatus(200);
        $response->assertSee('Invoices');
    }

    public function test_can_create_invoice()
    {
        $client = Client::create([
            'name' => 'Test Client',
            'client_code' => 'TC-001',
            'pan' => 'ABCDE1234F',
            'status' => 'Active',
            'category' => 'A'
        ]);

        $data = [
            'client_id' => $client->id,
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'invoice_number' => 'INV-TEST-001',
            'status' => 'Draft',
            'items' => [
                [
                    'description' => 'Consulting Services',
                    'quantity' => 1,
                    'rate' => 5000,
                    'amount' => 5000
                ]
            ],
            'subtotal' => 5000,
            'tax' => 900, // 18%
            'discount' => 0,
            'total_amount' => 5900
        ];

        $response = $this->post(route('invoices.store'), $data);

        $response->assertRedirect(route('invoices.index'));
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-TEST-001',
            'total_amount' => 5900
        ]);
    }

    public function test_can_view_invoice()
    {
        $client = Client::create([
            'name' => 'Test Client',
            'client_code' => 'TC-002',
            'pan' => 'FGHIJ5678K',
            'status' => 'Active',
            'category' => 'A'
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-VIEW-001',
            'date' => now(),
            'due_date' => now()->addDays(7),
            'status' => 'Draft',
            'subtotal' => 1000,
            'tax' => 180,
            'total_amount' => 1180
        ]);

        $response = $this->get(route('invoices.show', $invoice));
        $response->assertStatus(200);
        $response->assertSee('INV-VIEW-001');
    }
}

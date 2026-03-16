<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-subscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process active subscriptions and generate recurring invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $subscriptions = Subscription::where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->whereNull('next_billing_date')
                    ->orWhere('next_billing_date', '<=', $today);
            })
            ->get();

        $this->info("Processing " . $subscriptions->count() . " subscriptions...");

        foreach ($subscriptions as $subscription) {
            $this->processSubscription($subscription);
        }

        $this->info("Finished processing subscriptions.");
    }

    private function processSubscription(Subscription $subscription)
    {
        DB::transaction(function () use ($subscription) {
            $client = $subscription->client;
            $billingDate = $subscription->next_billing_date ?: Carbon::today();

            // 1. Create Invoice
            $invoiceNumber = 'INV-' . str_pad(Invoice::max('id') + 1, 5, '0', STR_PAD_LEFT);

            // GST and Tax Logic
            $firmStateCode = Setting::get('firm_state_code', '');
            $placeOfSupply = $client->billing_address ? $firmStateCode : $firmStateCode; // Basic logic, can be refined
            $isInterState = $firmStateCode && $placeOfSupply && $firmStateCode !== $placeOfSupply;

            $gstRate = Setting::get('default_gst_rate', 18);
            $taxAmount = $subscription->amount * $gstRate / 100;

            $cgst = 0;
            $sgst = 0;
            $igst = 0;
            if ($isInterState) {
                $igst = $taxAmount;
            } else {
                $cgst = $taxAmount / 2;
                $sgst = $taxAmount / 2;
            }

            $fy = $billingDate->month >= 4
                ? $billingDate->year . '-' . substr($billingDate->year + 1, 2)
                : ($billingDate->year - 1) . '-' . substr($billingDate->year, 2);

            $invoice = Invoice::create([
                'client_id' => $subscription->client_id,
                'branch_id' => $client->branch_id,
                'invoice_number' => $invoiceNumber,
                'date' => $billingDate,
                'due_date' => $billingDate->copy()->addDays(7), // Default 7 days
                'status' => 'Draft',
                'subtotal' => $subscription->amount,
                'tax' => $taxAmount,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => $igst,
                'total_amount' => $subscription->amount + $taxAmount,
                'place_of_supply' => $placeOfSupply,
                'financial_year' => $fy,
                'notes' => "Recurring Subscription: " . $subscription->name,
            ]);

            // 2. Create Invoice Item
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $subscription->name . " (Subscription)",
                'hsn_sac_code' => Setting::get('default_sac_code', '998231'),
                'gst_rate' => $gstRate,
                'quantity' => 1,
                'rate' => $subscription->amount,
                'amount' => $subscription->amount,
            ]);

            // 3. Update Subscription
            $subscription->last_billed_at = $billingDate;
            $subscription->next_billing_date = $subscription->calculateNextBillingDate();
            $subscription->save();

            $this->line("Generated invoice {$invoiceNumber} for client {$client->name}");
        });
    }
}

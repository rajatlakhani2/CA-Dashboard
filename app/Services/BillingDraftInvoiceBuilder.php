<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ServiceDue;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillingDraftInvoiceBuilder
{
    public function createDraftForClient(Client $client, ?array $dueIds = null, ?array $worksheetIds = null): Invoice
    {
        $dueQuery = ServiceDue::query()
            ->where('status', ServiceDue::STATUS_COMPLETED)
            ->where('billing_status', ServiceDue::BILLING_STATUS_UNBILLED)
            ->whereNull('invoice_id')
            ->whereHas('clientService', fn ($q) => $q->where('client_id', $client->id));

        if ($dueIds) {
            $dueQuery->whereIn('id', $dueIds);
        }

        $dues = $dueQuery->with('clientService.service')->get();

        $wsQuery = $client->worksheets()->where('is_billed', false)->whereNull('invoice_id');
        if ($worksheetIds) {
            $wsQuery->whereIn('id', $worksheetIds);
        }
        $worksheets = $wsQuery->get();

        if ($dues->isEmpty() && $worksheets->isEmpty()) {
            throw new \RuntimeException('No unbilled items for this client.');
        }

        return DB::transaction(function () use ($client, $dues, $worksheets) {
            $subtotal = 0;
            $itemsData = [];
            $firmState = Setting::get('firm_state_code', '24');
            $placeOfSupply = $client->gstin ? substr($client->gstin, 0, 2) : $firmState;
            $isInterState = $firmState && $placeOfSupply && $firmState !== $placeOfSupply;
            $totalCgst = $totalSgst = $totalIgst = 0;
            $defaultGst = (float) Setting::get('default_gst_rate', 18);
            $defaultSac = Setting::get('default_sac_code', '998221');

            foreach ($dues as $due) {
                $amount = (float) ($due->billing_amount ?? 0);
                $subtotal += $amount;
                $gstAmount = $amount * $defaultGst / 100;
                if ($isInterState) {
                    $totalIgst += $gstAmount;
                } else {
                    $totalCgst += $gstAmount / 2;
                    $totalSgst += $gstAmount / 2;
                }
                $itemsData[] = [
                    'description' => $due->clientService->service->name.' - '.$due->due_date->format('M Y'),
                    'hsn_sac_code' => $defaultSac,
                    'gst_rate' => $defaultGst,
                    'quantity' => 1,
                    'rate' => $amount,
                    'amount' => $amount,
                    'due_id' => $due->id,
                ];
            }

            foreach ($worksheets as $ws) {
                $amount = (float) $ws->amount;
                $subtotal += $amount;
                $gstAmount = $amount * $defaultGst / 100;
                if ($isInterState) {
                    $totalIgst += $gstAmount;
                } else {
                    $totalCgst += $gstAmount / 2;
                    $totalSgst += $gstAmount / 2;
                }
                $itemsData[] = [
                    'description' => $ws->description,
                    'hsn_sac_code' => $defaultSac,
                    'gst_rate' => $defaultGst,
                    'quantity' => 1,
                    'rate' => $amount,
                    'amount' => $amount,
                    'worksheet_id' => $ws->id,
                ];
            }

            $tax = round($totalCgst + $totalSgst + $totalIgst, 2);
            $invDate = Carbon::today();
            $fy = $invDate->month >= 4
                ? $invDate->year.'-'.substr((string) ($invDate->year + 1), 2)
                : ($invDate->year - 1).'-'.substr((string) $invDate->year, 2);

            $paymentDays = (int) Setting::get('invoice_payment_days', 15);

            $invoice = Invoice::create([
                'client_id' => $client->id,
                'branch_id' => $client->branch_id,
                'invoice_number' => $this->suggestInvoiceNumber(),
                'date' => $invDate,
                'due_date' => $invDate->copy()->addDays($paymentDays),
                'status' => Invoice::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'cgst' => round($totalCgst, 2),
                'sgst' => round($totalSgst, 2),
                'igst' => round($totalIgst, 2),
                'place_of_supply' => $placeOfSupply,
                'reverse_charge' => false,
                'financial_year' => $fy,
                'total_amount' => $subtotal + $tax,
            ]);

            foreach ($itemsData as $row) {
                $dueId = $row['due_id'] ?? null;
                $wsId = $row['worksheet_id'] ?? null;
                unset($row['due_id'], $row['worksheet_id']);
                $invoice->items()->create($row);

                if ($dueId) {
                    ServiceDue::where('id', $dueId)->update([
                        'invoice_id' => $invoice->id,
                        'billing_status' => ServiceDue::BILLING_STATUS_BILLED,
                    ]);
                }
                if ($wsId) {
                    \App\Models\ClientWorksheet::where('id', $wsId)->update([
                        'is_billed' => true,
                        'invoice_id' => $invoice->id,
                    ]);
                }
            }

            return $invoice;
        });
    }

    private function suggestInvoiceNumber(): string
    {
        $prefix = Setting::get('invoice_number_prefix', 'RLA/25-26/');
        $seq = str_pad((string) (Invoice::count() + 1), 4, '0', STR_PAD_LEFT);

        return rtrim($prefix, '/').'/'.$seq;
    }
}

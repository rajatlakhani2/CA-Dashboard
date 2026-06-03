<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Support\InvoicePdfData;
use App\Support\InvoicePaymentLinkBuilder;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::with('client')->whereHas('client');
        $this->scopeInvoicesToUser($query);

        // Tab Logic
        $tab = $request->get('tab', 'raised');
        if (auth()->user()?->isAssociate() && $tab === 'unbilled') {
            $tab = 'raised';
        }

        if ($tab === 'received') {
            $query->where('status', Invoice::STATUS_PAID);
        } elseif ($tab === 'raised') {
            $query->where('status', '!=', Invoice::STATUS_PAID);
        }

        // Additional optional filters
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $invoices = $query->latest('date')->paginate(20);

        // Counts for tabs
        $raisedCountQuery = Invoice::where('status', '!=', Invoice::STATUS_PAID);
        $receivedCountQuery = Invoice::where('status', Invoice::STATUS_PAID);
        $this->scopeInvoicesToUser($raisedCountQuery);
        $this->scopeInvoicesToUser($receivedCountQuery);
        $raisedCount = $raisedCountQuery->count();
        $receivedCount = $receivedCountQuery->count();

        $clients = $this->clientOptionsQuery()->orderBy('name')->get();

        $unbilledTasks = collect();
        $user = auth()->user();
        if ($user && ! $user->isAssociate()) {
            $unbilledTasks = \App\Models\Task::query()
                ->unbilledForUser($user)
                ->with(['client', 'assignee', 'creator'])
                ->latest('updated_at')
                ->get();
        }

        return view('invoices.index', compact('invoices', 'clients', 'unbilledTasks', 'tab', 'raisedCount', 'receivedCount'));
    }

    public function downloadPdf(Invoice $invoice)
    {
        $this->authorize('download', $invoice);

        $invoice->load(['client', 'items']);

        $pdf = Pdf::loadView('invoices.pdf', InvoicePdfData::for($invoice))
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $invoice->invoice_number).'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Invoice::class);

        $clients = $this->clientOptionsQuery()->orderBy('name')->get();
        // Generate next invoice number logic could go here
        $nextInvoiceNumber = 'INV-' . str_pad(Invoice::max('id') + 1, 5, '0', STR_PAD_LEFT);

        $prefillItems = session()->get('invoice_prefill_items', []);
        $prefillDues = session()->get('invoice_prefill_dues', []);
        $selectedClient = request('client_id');
        $linkedTask = null;

        // GST defaults
        $defaultSacCode = \App\Models\Setting::get('default_sac_code', '998231');
        $defaultGstRate = \App\Models\Setting::get('default_gst_rate', '18');
        $firmStateCode = \App\Models\Setting::get('firm_state_code', '');
        $states = $this->getIndianStates();

        // Task Prefill Logic
        if (request()->has('task_id')) {
            $task = \App\Models\Task::find(request('task_id'));
            if ($task) {
                $selectedClient = $task->client_id;
                $prefillItems[] = [
                    'description' => "Task: " . $task->title,
                    'hsn_sac_code' => $defaultSacCode,
                    'gst_rate' => $defaultGstRate,
                    'quantity' => 1,
                    'rate' => 0
                ];
                $linkedTask = $task->id;
            }
        }

        return view('invoices.create', compact('clients', 'nextInvoiceNumber', 'prefillItems', 'prefillDues', 'selectedClient', 'linkedTask', 'defaultSacCode', 'defaultGstRate', 'firmStateCode', 'states'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\StoreInvoiceRequest $request)
    {
        $this->authorize('create', Invoice::class);

        $this->authorize('createForClient', [Invoice::class, Client::findOrFail($request->client_id)]);

        DB::transaction(function () use ($request) {
            $subtotal = 0;
            $totalCgst = 0;
            $totalSgst = 0;
            $totalIgst = 0;
            $itemsData = [];

            $firmStateCode = \App\Models\Setting::get('firm_state_code', '');
            $placeOfSupply = $request->input('place_of_supply', $firmStateCode);
            $isInterState = $firmStateCode && $placeOfSupply && $firmStateCode !== $placeOfSupply;

            foreach ($request->items as $item) {
                $amount = $item['quantity'] * $item['rate'];
                $subtotal += $amount;
                $gstRate = $item['gst_rate'] ?? 18;
                $gstAmount = $amount * $gstRate / 100;

                if ($isInterState) {
                    $totalIgst += $gstAmount;
                } else {
                    $totalCgst += $gstAmount / 2;
                    $totalSgst += $gstAmount / 2;
                }

                $itemsData[] = [
                    'description' => $item['description'],
                    'hsn_sac_code' => $item['hsn_sac_code'] ?? \App\Models\Setting::get('default_sac_code', '998231'),
                    'gst_rate' => $gstRate,
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'amount' => $amount,
                ];
            }

            $tax = round($totalCgst + $totalSgst + $totalIgst, 2);
            $total = $subtotal + $tax;

            // Determine financial year
            $invDate = \Carbon\Carbon::parse($request->date);
            $fy = $invDate->month >= 4
                ? $invDate->year . '-' . substr($invDate->year + 1, 2)
                : ($invDate->year - 1) . '-' . substr($invDate->year, 2);

            $invoice = Invoice::create([
                'client_id' => $request->client_id,
                'invoice_number' => $request->invoice_number,
                'date' => $request->date,
                'due_date' => $request->due_date,
                'status' => Invoice::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'cgst' => round($totalCgst, 2),
                'sgst' => round($totalSgst, 2),
                'igst' => round($totalIgst, 2),
                'place_of_supply' => $placeOfSupply,
                'reverse_charge' => $request->boolean('reverse_charge'),
                'financial_year' => $fy,
                'total_amount' => $total,
                'notes' => $request->notes,
                'reference_number' => $request->reference_number,
                'work_period' => $request->work_period,
                'project_name' => $request->project_name,
            ]);

            foreach ($itemsData as $data) {
                $invoice->items()->create($data);
            }

            // Link Service Dues if present
            if ($request->has('linked_service_dues')) {
                $dueIds = explode(',', $request->linked_service_dues);
                \App\Models\ServiceDue::whereIn('id', $dueIds)->update([
                    'invoice_id' => $invoice->id,
                    'billing_status' => \App\Models\ServiceDue::BILLING_STATUS_BILLED
                ]);
            }

            // Mark Task as Billed
            if ($request->filled('linked_task')) {
                \App\Models\Task::where('id', $request->linked_task)->update(['is_billed' => true]);
            }

            $this->syncInvoicePaymentUrl($invoice->fresh());
        });

        // Clear session data
        session()->forget(['invoice_prefill_items', 'invoice_prefill_dues']);

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'items']);
        $this->syncInvoicePaymentUrl($invoice);
        $paymentQrUrl = app(InvoicePaymentLinkBuilder::class)->qrImageUrl($invoice->payment_url);

        return view('invoices.show', compact('invoice', 'paymentQrUrl'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $invoice->load(['client', 'items']);
        $clients = $this->clientOptionsQuery()->orderBy('name')->get();
        $defaultSacCode = \App\Models\Setting::get('default_sac_code', '998231');
        $defaultGstRate = \App\Models\Setting::get('default_gst_rate', '18');
        $firmStateCode = \App\Models\Setting::get('firm_state_code', '');
        $states = $this->getIndianStates();
        return view('invoices.edit', compact('invoice', 'clients', 'defaultSacCode', 'defaultGstRate', 'firmStateCode', 'states'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\UpdateInvoiceRequest $request, Invoice $invoice, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('update', $invoice);

        $before = [
            'status' => $invoice->status,
            'total_amount' => (float) $invoice->total_amount,
            'client_id' => (int) $invoice->client_id,
            'invoice_number' => $invoice->invoice_number,
        ];

        DB::transaction(function () use ($request, $invoice) {
            $subtotal = 0;
            $totalCgst = 0;
            $totalSgst = 0;
            $totalIgst = 0;

            $firmStateCode = \App\Models\Setting::get('firm_state_code', '');
            $placeOfSupply = $request->input('place_of_supply', $firmStateCode);
            $isInterState = $firmStateCode && $placeOfSupply && $firmStateCode !== $placeOfSupply;

            foreach ($request->items as $item) {
                $amount = $item['quantity'] * $item['rate'];
                $subtotal += $amount;
                $gstRate = $item['gst_rate'] ?? 18;
                $gstAmount = $amount * $gstRate / 100;

                if ($isInterState) {
                    $totalIgst += $gstAmount;
                } else {
                    $totalCgst += $gstAmount / 2;
                    $totalSgst += $gstAmount / 2;
                }
            }

            $tax = round($totalCgst + $totalSgst + $totalIgst, 2);
            $total = $subtotal + $tax;

            $invDate = \Carbon\Carbon::parse($request->date);
            $fy = $invDate->month >= 4
                ? $invDate->year . '-' . substr($invDate->year + 1, 2)
                : ($invDate->year - 1) . '-' . substr($invDate->year, 2);

            $invoice->update([
                'client_id' => $request->client_id,
                'invoice_number' => $request->invoice_number,
                'date' => $request->date,
                'due_date' => $request->due_date,
                'status' => $request->status,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'cgst' => round($totalCgst, 2),
                'sgst' => round($totalSgst, 2),
                'igst' => round($totalIgst, 2),
                'place_of_supply' => $placeOfSupply,
                'reverse_charge' => $request->boolean('reverse_charge'),
                'financial_year' => $fy,
                'total_amount' => $total,
                'notes' => $request->notes,
                'reference_number' => $request->reference_number,
                'work_period' => $request->work_period,
                'project_name' => $request->project_name,
            ]);

            // Sync Items
            $keepIds = collect($request->items)->pluck('id')->filter()->toArray();
            $invoice->items()->whereNotIn('id', $keepIds)->delete();

            foreach ($request->items as $itemData) {
                $amount = $itemData['quantity'] * $itemData['rate'];

                if (isset($itemData['id'])) {
                    $invoice->items()->where('id', $itemData['id'])->update([
                        'description' => $itemData['description'],
                        'hsn_sac_code' => $itemData['hsn_sac_code'] ?? '',
                        'gst_rate' => $itemData['gst_rate'] ?? 18,
                        'quantity' => $itemData['quantity'],
                        'rate' => $itemData['rate'],
                        'amount' => $amount,
                    ]);
                } else {
                    $invoice->items()->create([
                        'description' => $itemData['description'],
                        'hsn_sac_code' => $itemData['hsn_sac_code'] ?? '',
                        'gst_rate' => $itemData['gst_rate'] ?? 18,
                        'quantity' => $itemData['quantity'],
                        'rate' => $itemData['rate'],
                        'amount' => $amount,
                    ]);
                }
            }

            $this->syncInvoicePaymentUrl($invoice->fresh());
        });

        $audit->invoiceUpdated($invoice->fresh(['client']), $before);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
    }

    protected function syncInvoicePaymentUrl(Invoice $invoice): void
    {
        $url = app(InvoicePaymentLinkBuilder::class)->build($invoice);
        if ($invoice->payment_url !== $url) {
            $invoice->forceFill(['payment_url' => $url])->saveQuietly();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('delete', $invoice);

        $audit->invoiceDeleted($invoice);
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function sendEmail(Invoice $invoice, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('send', $invoice);

        $invoice->load('client');

        $email = $invoice->client->invoice_email ?? $invoice->client->primary_contact_email;

        if (!$email) {
            return back()->with('error', 'No email address found for this client.');
        }

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\InvoiceMail($invoice));
            $audit->invoiceSent($invoice, 'email');

            if ($invoice->status === Invoice::STATUS_DRAFT
                && $invoice->due_date
                && $invoice->due_date->isPast()) {
                $invoice->update(['status' => Invoice::STATUS_OVERDUE]);
            }

            return back()->with('success', "Invoice emailed to {$email} successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function sendWhatsApp(Invoice $invoice, \App\Services\WhatsAppService $whatsAppService, \App\Services\SensitiveActionLogger $audit)
    {
        $this->authorize('send', $invoice);

        $invoice->load('client');
        $client = $invoice->client;

        if (!$client || empty($client->mobile_number)) {
            return back()->with('error', 'Client does not have a valid mobile number.');
        }

        $amount = number_format($invoice->total_amount);
        $dueDate = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : 'Immediately';
        $message = "🔔 *Payment Reminder*\n\nDear {$client->name},\nThis is a gentle reminder that your payment of ₹{$amount} for Invoice #{$invoice->invoice_number} is due on {$dueDate}.\n\nPlease pay at the earliest.";

        $result = $whatsAppService->sendMessage($client->mobile_number, $message);

        if ($result['success']) {
            $audit->invoiceSent($invoice, 'whatsapp');

            return back()->with('success', "WhatsApp reminder sent to {$client->name} successfully.");
        }

        return back()->with('error', "Failed to send WhatsApp: " . $result['message']);
    }

    private function getIndianStates(): array
    {
        return [
            '01' => '01 - Jammu & Kashmir',
            '02' => '02 - Himachal Pradesh',
            '03' => '03 - Punjab',
            '04' => '04 - Chandigarh',
            '05' => '05 - Uttarakhand',
            '06' => '06 - Haryana',
            '07' => '07 - Delhi',
            '08' => '08 - Rajasthan',
            '09' => '09 - Uttar Pradesh',
            '10' => '10 - Bihar',
            '11' => '11 - Sikkim',
            '12' => '12 - Arunachal Pradesh',
            '13' => '13 - Nagaland',
            '14' => '14 - Manipur',
            '15' => '15 - Mizoram',
            '16' => '16 - Tripura',
            '17' => '17 - Meghalaya',
            '18' => '18 - Assam',
            '19' => '19 - West Bengal',
            '20' => '20 - Jharkhand',
            '21' => '21 - Odisha',
            '22' => '22 - Chhattisgarh',
            '23' => '23 - Madhya Pradesh',
            '24' => '24 - Gujarat',
            '26' => '26 - Dadra & Nagar Haveli and Daman & Diu',
            '27' => '27 - Maharashtra',
            '29' => '29 - Karnataka',
            '30' => '30 - Goa',
            '31' => '31 - Lakshadweep',
            '32' => '32 - Kerala',
            '33' => '33 - Tamil Nadu',
            '34' => '34 - Puducherry',
            '35' => '35 - Andaman & Nicobar Islands',
            '36' => '36 - Telangana',
            '37' => '37 - Andhra Pradesh',
            '38' => '38 - Ladakh',
        ];
    }

    private function scopeInvoicesToUser($query): void
    {
        $user = auth()->user();

        if ($user?->isAssociate()) {
            $query->whereHas('client', function ($clientQuery) use ($user) {
                $clientQuery->where('manager_id', $user->id)
                    ->where('approval_status', Client::APPROVAL_APPROVED);
            });

            return;
        }

        if (! $user?->isManager() || ! $user->branch_id) {
            return;
        }

        $query->where(function ($q) use ($user) {
            $q->where('branch_id', $user->branch_id)
                ->orWhere(function ($q) use ($user) {
                    $q->whereNull('branch_id')
                        ->whereHas('client', function ($clientQuery) use ($user) {
                            $clientQuery->whereNull('branch_id')
                                ->orWhere('branch_id', $user->branch_id);
                        });
                });
        });
    }

    private function clientOptionsQuery()
    {
        return Client::query()->visibleTo(auth()->user());
    }
}

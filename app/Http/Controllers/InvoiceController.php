<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Invoice::with('client');

        // Tab Logic
        $tab = $request->get('tab', 'raised'); // Default to 'raised' (Unpaid)

        if ($tab === 'received') {
            $query->where('status', 'Paid');
        } elseif ($tab === 'raised') {
            $query->where('status', '!=', 'Paid');
        }

        // Additional optional filters
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $invoices = $query->latest('date')->paginate(20);

        // Counts for tabs
        $raisedCount = Invoice::where('status', '!=', 'Paid')->count();
        $receivedCount = Invoice::where('status', 'Paid')->count();

        $clients = Client::orderBy('name')->get();

        $unbilledTasks = \App\Models\Task::where('assigned_to', auth()->id())
            ->where('status', 'Completed')
            ->where('is_billed', false)
            ->with('client')
            ->get();

        return view('invoices.index', compact('invoices', 'clients', 'unbilledTasks', 'tab', 'raisedCount', 'receivedCount'));
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['client', 'items']);
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Client::orderBy('name')->get();
        // Generate next invoice number logic could go here
        $nextInvoiceNumber = 'INV-' . str_pad(Invoice::max('id') + 1, 5, '0', STR_PAD_LEFT);

        $prefillItems = session()->get('invoice_prefill_items', []);
        $prefillDues = session()->get('invoice_prefill_dues', []);
        $selectedClient = request('client_id');
        $linkedTask = null;

        // Task Prefill Logic
        if (request()->has('task_id')) {
            $task = \App\Models\Task::find(request('task_id'));
            if ($task) {
                $selectedClient = $task->client_id;
                $prefillItems[] = [
                    'description' => "Task: " . $task->title,
                    'quantity' => 1,
                    'rate' => 0 // User to fill
                ];
                $linkedTask = $task->id;
            }
        }

        return view('invoices.create', compact('clients', 'nextInvoiceNumber', 'prefillItems', 'prefillDues', 'selectedClient', 'linkedTask'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => 'required|unique:invoices,invoice_number',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $amount = $item['quantity'] * $item['rate'];
                $subtotal += $amount;
                $itemsData[] = [
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'amount' => $amount,
                ];
            }

            // Simple tax logic (e.g. 18% GST) or manual input. 
            $tax = $request->input('tax', 0);
            $total = $subtotal + $tax;

            $invoice = Invoice::create([
                'client_id' => $request->client_id,
                'invoice_number' => $request->invoice_number,
                'date' => $request->date,
                'due_date' => $request->due_date,
                'status' => 'Draft', // Default to Draft
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total_amount' => $total,
                'notes' => $request->notes,
            ]);

            foreach ($itemsData as $data) {
                $invoice->items()->create($data);
            }

            // Link Service Dues if present
            if ($request->has('linked_service_dues')) {
                $dueIds = explode(',', $request->linked_service_dues);
                \App\Models\ServiceDue::whereIn('id', $dueIds)->update([
                    'invoice_id' => $invoice->id,
                    'billing_status' => 'Billed'
                ]);
            }

            // Mark Task as Billed
            if ($request->filled('linked_task')) {
                \App\Models\Task::where('id', $request->linked_task)->update(['is_billed' => true]);
            }
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
        $invoice->load(['client', 'items']);
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        $invoice->load(['client', 'items']);
        $clients = Client::orderBy('name')->get();
        return view('invoices.edit', compact('invoice', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => 'required|unique:invoices,invoice_number,' . $invoice->id, // Allow same number for this ID
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'status' => 'required|in:Draft,Sent,Paid,Overdue',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $invoice) {
            $subtotal = 0;
            $itemsData = []; // To store data for syncing

            // We will delete all existing items and recreate/update them. 
            // A simpler approach for this scale is to delete all and recreate, 
            // but preserving IDs is better for potential specialized future logic.
            // For now, let's go with "Delete All items and Recreate" for simplicity and correctness of totals. 
            // (Or we can use standard sync logic if we tracked IDs in the form, which we did).

            // Let's iterate and prepare data
            foreach ($request->items as $item) {
                $amount = $item['quantity'] * $item['rate'];
                $subtotal += $amount;
            }

            $tax = 0;
            $total = $subtotal + $tax;

            // Update Invoice Header
            $invoice->update([
                'client_id' => $request->client_id,
                'invoice_number' => $request->invoice_number,
                'date' => $request->date,
                'due_date' => $request->due_date,
                'status' => $request->status,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total_amount' => $total,
                'notes' => $request->notes,
            ]);

            // Sync Items
            // 1. Get IDs of items present in request
            $keepIds = collect($request->items)->pluck('id')->filter()->toArray();

            // 2. Delete items not in request
            $invoice->items()->whereNotIn('id', $keepIds)->delete();

            // 3. Create or Update items
            foreach ($request->items as $itemData) {
                $amount = $itemData['quantity'] * $itemData['rate'];

                if (isset($itemData['id'])) {
                    $invoice->items()->where('id', $itemData['id'])->update([
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'rate' => $itemData['rate'],
                        'amount' => $amount,
                    ]);
                } else {
                    $invoice->items()->create([
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'rate' => $itemData['rate'],
                        'amount' => $amount,
                    ]);
                }
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }
}

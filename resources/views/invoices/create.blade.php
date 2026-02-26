@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Create Invoice</span>
    <a href="{{ route('invoices.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Back to Invoices</a>
</div>
@endsection

@section('content')
<div class="max-w-5xl mx-auto">
    <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
        @csrf
        @if(isset($prefillDues))
        <input type="hidden" name="linked_service_dues" value="{{ implode(',', $prefillDues) }}">
        @endif
        @if(isset($linkedTask))
        <input type="hidden" name="linked_task" value="{{ $linkedTask }}">
        @endif
        <div class="bg-bg-card shadow sm:rounded-lg border border-line">

            <!-- Header Section -->
            <div class="px-4 py-5 sm:p-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                <!-- Client -->
                <div class="col-span-1">
                    <label for="client_id" class="block text-sm font-medium text-text-secondary">Client</label>
                    <select id="client_id" name="client_id" required class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ (isset($selectedClient) && $selectedClient == $client->id) ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Invoice Number -->
                <div class="col-span-1">
                    <label for="invoice_number" class="block text-sm font-medium text-text-secondary">Invoice Number</label>
                    <input type="text" name="invoice_number" id="invoice_number" value="{{ $nextInvoiceNumber }}" required class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- Dates -->
                <div class="col-span-1">
                    <label for="date" class="block text-sm font-medium text-text-secondary">Invoice Date</label>
                    <input type="date" name="date" id="date" value="{{ date('Y-m-d') }}" required class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div class="col-span-1">
                    <label for="due_date" class="block text-sm font-medium text-text-secondary">Due Date</label>
                    <input type="date" name="due_date" id="due_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>

            <div class="border-t border-line"></div>

            <!-- Line Items -->
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-text-main mb-4">Items</h3>

                <table class="min-w-full divide-y divide-line">
                    <thead>
                        <tr>
                            <th class="text-left text-xs font-medium text-text-secondary uppercase tracking-wider w-1/2">Description</th>
                            <th class="text-left text-xs font-medium text-text-secondary uppercase tracking-wider w-24">Qty</th>
                            <th class="text-left text-xs font-medium text-text-secondary uppercase tracking-wider w-32">Rate</th>
                            <th class="text-right text-xs font-medium text-text-secondary uppercase tracking-wider w-32">Amount</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="items-container" class="divide-y divide-line">
                        <!-- JS renders rows here -->
                    </tbody>
                </table>

                <button type="button" onclick="addItem()" class="mt-4 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    + Add Item
                </button>
            </div>

            <div class="border-t border-line"></div>

            <!-- Totals & Notes -->
            <div class="px-4 py-5 sm:p-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="notes" class="block text-sm font-medium text-text-secondary">Notes</label>
                    <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                </div>

                <div class="flex flex-col items-end justify-start space-y-2">
                    <div class="flex justify-between w-48 text-sm">
                        <span class="text-text-secondary">Subtotal:</span>
                        <span class="font-medium text-text-main" id="subtotal-display">₹ 0.00</span>
                    </div>
                    <div class="flex justify-between w-48 text-sm">
                        <span class="text-text-secondary">Tax (0%):</span>
                        <span class="font-medium text-text-main">₹ 0.00</span>
                    </div>
                    <div class="flex justify-between w-48 text-base font-bold border-t border-line pt-2">
                        <span class="text-text-main">Total:</span>
                        <span class="text-indigo-600" id="total-display">₹ 0.00</span>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-4 py-3 bg-bg-body text-right sm:px-6 rounded-b-lg">
                <button type="button" onclick="window.history.back()" class="bg-white py-2 px-4 border border-line rounded-md shadow-sm text-sm font-medium text-text-secondary hover:bg-gray-50 focus:outline-none mr-2">
                    Cancel
                </button>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create Invoice
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    let itemIndex = 0;
    const prefillItems = @json($prefillItems ?? []);

    function addItem(data = null) {
        const container = document.getElementById('items-container');
        const row = document.createElement('tr');

        const description = data ? data.description : '';
        const quantity = data ? data.quantity : 1;
        const rate = data ? data.rate : 0;

        row.innerHTML = `
            <td class="py-2 pr-2">
                <input type="text" name="items[${itemIndex}][description]" value="${description}" required class="block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </td>
            <td class="py-2 pr-2">
                <input type="number" name="items[${itemIndex}][quantity]" value="${quantity}" step="0.01" min="0" oninput="calculateTotal()" required class="block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </td>
            <td class="py-2 pr-2">
                <input type="number" name="items[${itemIndex}][rate]" value="${rate}" step="0.01" min="0" oninput="calculateTotal()" required class="block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </td>
            <td class="py-2 text-right text-sm text-text-main font-medium item-amount">
                ₹ 0.00
            </td>
            <td class="py-2 text-center">
                <button type="button" onclick="this.closest('tr').remove(); calculateTotal()" class="text-red-600 hover:text-red-900">
                    &times;
                </button>
            </td>
        `;
        container.appendChild(row);
        itemIndex++;
        calculateTotal();
    }

    function calculateTotal() {
        let subtotal = 0;
        const rows = document.querySelectorAll('#items-container tr');

        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
            const rate = parseFloat(row.querySelector('input[name*="[rate]"]').value) || 0;
            const amount = qty * rate;

            subtotal += amount;
            row.querySelector('.item-amount').textContent = '₹ ' + amount.toFixed(2);
        });

        document.getElementById('subtotal-display').textContent = '₹ ' + subtotal.toFixed(2);
        document.getElementById('total-display').textContent = '₹ ' + subtotal.toFixed(2); // Tax is 0 for now
    }

    // Initialize items
    if (prefillItems.length > 0) {
        prefillItems.forEach(item => addItem(item));
    } else {
        addItem(); // Add one empty row by default
    }
</script>
@endsection
@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $invoiceModel = $invoice ?? null;
    $initialLines = collect($prefillItems ?? [])->map(function ($item) {
        $amount = $item['amount'] ?? ((float) ($item['quantity'] ?? 1) * (float) ($item['rate'] ?? 0));
        return [
            'id' => $item['id'] ?? null,
            'description' => $item['description'] ?? '',
            'amount' => $amount > 0 ? $amount : '',
        ];
    })->values()->all();

    if ($isEdit && $invoiceModel && empty($initialLines)) {
        $initialLines = $invoiceModel->items->map(fn ($item) => [
            'id' => $item->id,
            'description' => $item->description,
            'amount' => $item->amount,
        ])->values()->all();
    }

    if (empty($initialLines)) {
        $initialLines = [['id' => null, 'description' => '', 'amount' => '']];
    }

    $initialLines = collect($initialLines)->values()->map(function ($line, $i) {
        return array_merge($line, ['_key' => 'line-' . $i . '-' . uniqid()]);
    })->all();

    $clientsList = $clients->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values();
    $paymentDays = (int) (\App\Models\Setting::get('invoice_payment_days', 15) ?: 15);
    $formClientId = old('client_id', $selectedClient ?? $invoiceModel?->client_id ?? '');
    $formInvoiceNumber = old('invoice_number', $nextInvoiceNumber ?? $invoiceModel?->invoice_number ?? '');
    $formDate = old('date', isset($invoiceModel) ? $invoiceModel->date->format('Y-m-d') : date('Y-m-d'));
    $formDueDate = old('due_date', isset($invoiceModel) ? $invoiceModel->due_date->format('Y-m-d') : date('Y-m-d', strtotime('+7 days')));
@endphp

@push('head_styles')
@include('dashboard.partials.premium-styles')
<style>
    [x-cloak] { display: none !important; }
    .invoice-shell { max-width: 72rem; margin: 0 auto; }
    .invoice-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .invoice-preview {
        background: linear-gradient(135deg, #312e81 0%, #4338ca 50%, #6366f1 100%);
        border-radius: 16px;
        color: #fff;
    }
    .line-item-row {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .line-item-row:focus-within {
        border-color: #a5b4fc;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }
    .summary-sticky { position: sticky; top: 1.25rem; }
    .chip-btn {
        border-radius: 9999px;
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #4b5563;
        transition: all 0.15s;
    }
    .chip-btn:hover { border-color: #c7d2fe; background: #eef2ff; color: #4338ca; }
    .chip-btn.active { border-color: #6366f1; background: #eef2ff; color: #4338ca; }
</style>
@endpush

<div class="invoice-shell px-1 pb-16" x-data="invoiceForm()" x-cloak>

    {{-- Page header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ $isEdit ? route('invoices.show', $invoiceModel) : route('invoices.index') }}"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 shadow-sm hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">{{ $isEdit ? 'Edit invoice' : 'New invoice' }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">Live preview updates as you type</p>
            </div>
        </div>
        <div class="hidden sm:flex items-center gap-2 text-xs text-gray-500">
            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 text-indigo-700 px-2.5 py-1 font-semibold border border-indigo-100">
                GST preview at <span x-text="gstRate"></span>%
            </span>
        </div>
    </div>

    @if ($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc list-inside space-y-0.5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Live preview strip --}}
    <div class="invoice-preview p-5 sm:p-6 mb-6 shadow-lg shadow-indigo-900/20">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-200">Invoice preview</p>
                <p class="font-display text-2xl sm:text-3xl font-bold mt-1" x-text="invoiceNumber || '—'"></p>
                <p class="text-indigo-100 text-sm mt-2">
                    <span x-text="clientLabel || 'Select a client'"></span>
                    <span class="text-indigo-300 mx-1">·</span>
                    <span x-text="formattedDate()"></span>
                </p>
            </div>
            <div class="text-right">
                <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-200">Amount due</p>
                <p class="text-3xl sm:text-4xl font-extrabold tabular-nums mt-1" x-text="formatMoney(total)"></p>
                <p class="text-xs text-indigo-200 mt-1" x-text="lineCount + ' line item' + (lineCount === 1 ? '' : 's')"></p>
            </div>
        </div>
    </div>

    <form action="{{ $formAction }}" method="POST" @submit="validateBeforeSubmit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        @if(isset($prefillDues))
        <input type="hidden" name="linked_service_dues" value="{{ implode(',', $prefillDues) }}">
        @endif
        @if(isset($prefillWorksheets))
        <input type="hidden" name="linked_worksheets" value="{{ implode(',', $prefillWorksheets) }}">
        @endif
        @if(isset($linkedTask))
        <input type="hidden" name="linked_task" value="{{ $linkedTask }}">
        @endif

        <div class="lg:col-span-2 space-y-5">

            {{-- Bill to & dates --}}
            <div class="invoice-card p-5 sm:p-6">
                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4">Bill to & dates</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Client <span class="text-red-500">*</span></label>
                        <select name="client_id" x-model="clientId" required
                            class="block w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:bg-white">
                            <option value="">Choose client…</option>
                            @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Invoice number <span class="text-red-500">*</span></label>
                        <input type="text" name="invoice_number" x-model="invoiceNumber" required
                            class="block w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 px-3 text-sm font-mono shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:bg-white">
                    </div>
                    @if($isEdit)
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Status</label>
                        <select name="status" x-model="status" required class="block w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(\App\Models\Invoice::selectableStatuses() as $st)
                            <option value="{{ $st }}">{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Invoice date</label>
                        <input type="date" name="date" x-model="date" @change="syncDueFromInvoiceDate()" required
                            class="block w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Due date</label>
                        <input type="date" name="due_date" x-model="dueDate" required
                            class="block w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:bg-white">
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <button type="button" class="chip-btn" :class="dueChipActive(7) && 'active'" @click="setDueDays(7)">+7 days</button>
                            <button type="button" class="chip-btn" :class="dueChipActive(15) && 'active'" @click="setDueDays(15)">+15 days</button>
                            <button type="button" class="chip-btn" :class="dueChipActive(30) && 'active'" @click="setDueDays(30)">+30 days</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Place of supply</label>
                        <input type="text" name="place_of_supply" x-model="placeOfSupply" placeholder="e.g. Gujarat"
                            class="block w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Work period</label>
                        <input type="text" name="work_period" x-model="workPeriod" placeholder="e.g. Apr 2026"
                            class="block w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:bg-white">
                    </div>
                </div>
                <button type="button" @click="showAdvanced = !showAdvanced" class="mt-4 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                    <span x-text="showAdvanced ? '− Hide' : '+'"></span> PO, project & GST options
                </button>
                <div x-show="showAdvanced" x-transition class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">PO / Reference no.</label>
                        <input type="text" name="reference_number" x-model="referenceNumber"
                            class="block w-full rounded-xl border-gray-200 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Project name</label>
                        <input type="text" name="project_name" x-model="projectName"
                            class="block w-full rounded-xl border-gray-200 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 cursor-pointer hover:bg-indigo-50">
                            <input type="checkbox" name="reverse_charge" value="1" :checked="reverseCharge" @change="reverseCharge = $event.target.checked"
                                class="rounded text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Reverse charge applicable (GST)</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Line items --}}
            <div class="invoice-card p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Services & fees</h2>
                        <p class="text-xs text-gray-500 mt-0.5">One row per service — enter description and amount only</p>
                    </div>
                    <button type="button" @click="addLine()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-md shadow-indigo-600/25 hover:bg-indigo-700 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add line
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(line, index) in lines" :key="line._key">
                        <div class="line-item-row bg-gray-50/80 p-3 sm:p-4">
                            <div class="flex gap-3 items-start">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-xs font-bold text-indigo-700" x-text="index + 1"></span>
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-12 gap-3">
                                    <div class="sm:col-span-8">
                                        <label class="sr-only">Description</label>
                                        <input type="text" :name="'items[' + index + '][description]'" x-model="line.description" required
                                            placeholder="e.g. GSTR-3B return — Apr 2026, Annual audit fee…"
                                            class="block w-full rounded-lg border-gray-200 bg-white py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <template x-if="line.id">
                                            <input type="hidden" :name="'items[' + index + '][id]'" :value="line.id">
                                        </template>
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label class="sr-only">Amount</label>
                                        <div class="relative">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-gray-400">₹</span>
                                            <input type="number" x-model="line.amount" step="0.01" min="0" required
                                                placeholder="0.00"
                                                class="block w-full rounded-lg border-gray-200 bg-white py-2.5 pl-8 pr-3 text-sm text-right font-semibold tabular-nums shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <input type="hidden" :name="'items[' + index + '][quantity]'" value="1">
                                        <input type="hidden" :name="'items[' + index + '][rate]'" :value="line.amount">
                                        <input type="hidden" :name="'items[' + index + '][gst_rate]'" :value="gstRate">
                                        <input type="hidden" :name="'items[' + index + '][hsn_sac_code]'" value="{{ $defaultSacCode ?? '998231' }}">
                                    </div>
                                </div>
                                <button type="button" @click="removeLine(index)" :disabled="lines.length <= 1"
                                    class="shrink-0 flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-30 disabled:pointer-events-none transition"
                                    title="Remove line">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <p x-show="lineCount === 0" class="text-center py-8 text-sm text-gray-400">Add at least one service line</p>
            </div>

            {{-- Notes --}}
            <div class="invoice-card p-5 sm:p-6">
                <label for="invoice-notes" class="block text-sm font-bold text-gray-900 uppercase tracking-wide mb-2">Notes for client</label>
                <textarea id="invoice-notes" name="notes" rows="3" x-model="invoiceNotes" placeholder="Payment terms, bank details reminder, thank-you note…"
                    class="block w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:bg-white"></textarea>
            </div>
        </div>

        {{-- Summary sidebar --}}
        <div class="lg:col-span-1">
            <div class="summary-sticky space-y-4">
                <div class="kpi-card kpi-blue cursor-default">
                    <p class="kpi-label">Subtotal</p>
                    <p class="kpi-value text-2xl tabular-nums" x-text="formatMoney(subtotal)"></p>
                    <p class="kpi-sub" x-text="lineCount + ' service line(s)'"></p>
                </div>
                <div class="invoice-card p-5 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">GST (<span x-text="gstRate"></span>%)</span>
                        <span class="font-semibold text-gray-900 tabular-nums" x-text="formatMoney(taxAmount)"></span>
                    </div>
                    <div class="flex justify-between text-base font-bold border-t border-gray-100 pt-3">
                        <span class="text-gray-900">Total</span>
                        <span class="text-indigo-600 tabular-nums" x-text="formatMoney(total)"></span>
                    </div>
                    <p class="text-[11px] text-gray-400 leading-relaxed">GST split (CGST/SGST/IGST) is calculated on save based on place of supply.</p>
                </div>
                <div class="flex flex-col gap-2">
                    <button type="submit"
                        class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-indigo-600 py-3 px-4 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $isEdit ? 'Save invoice' : 'Create invoice' }}
                    </button>
                    <a href="{{ $isEdit ? route('invoices.show', $invoiceModel) : route('invoices.index') }}"
                        class="w-full text-center rounded-xl border border-gray-200 bg-white py-2.5 px-4 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function invoiceForm() {
    return {
        lines: @json($initialLines),
        clients: @json($clientsList),
        gstRate: {{ (float) ($defaultGstRate ?? 18) }},
        paymentDays: {{ $paymentDays }},
        clientId: @json((string) $formClientId),
        invoiceNumber: @json($formInvoiceNumber),
        date: @json($formDate),
        dueDate: @json($formDueDate),
        placeOfSupply: @json(old('place_of_supply', $invoiceModel?->place_of_supply ?? ($firmStateCode ?? ''))),
        referenceNumber: @json(old('reference_number', $invoiceModel?->reference_number ?? '')),
        workPeriod: @json(old('work_period', $invoiceModel?->work_period ?? '')),
        projectName: @json(old('project_name', $invoiceModel?->project_name ?? '')),
        invoiceNotes: @json(old('notes', $invoiceModel?->notes ?? '')),
        reverseCharge: @json((bool) old('reverse_charge', $invoiceModel?->reverse_charge ?? false)),
        status: @json(old('status', $invoiceModel?->status ?? 'Draft')),
        showAdvanced: false,
        _dueOffset: null,

        get clientLabel() {
            const c = this.clients.find(function (x) { return String(x.id) === String(this.clientId); }.bind(this));
            return c ? c.name : '';
        },
        get subtotal() {
            return this.lines.reduce(function (sum, line) {
                return sum + (parseFloat(line.amount) || 0);
            }, 0);
        },
        get taxAmount() {
            return Math.round(this.subtotal * this.gstRate / 100 * 100) / 100;
        },
        get total() {
            return this.subtotal + this.taxAmount;
        },
        get lineCount() {
            return this.lines.filter(function (l) { return (l.description || '').trim() || parseFloat(l.amount); }).length;
        },

        formatMoney(value) {
            return '₹ ' + (parseFloat(value) || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formattedDate() {
            if (!this.date) return '—';
            try {
                return new Date(this.date + 'T12:00:00').toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
            } catch (e) { return this.date; }
        },
        addLine() {
            this.lines.push({ id: null, description: '', amount: '', _key: 'line-' + this.lines.length + '-' + Date.now() });
        },
        removeLine(index) {
            if (this.lines.length <= 1) return;
            this.lines.splice(index, 1);
        },
        setDueDays(days) {
            if (!this.date) return;
            const base = new Date(this.date + 'T12:00:00');
            base.setDate(base.getDate() + days);
            this.dueDate = base.toISOString().slice(0, 10);
            this._dueOffset = days;
        },
        dueChipActive(days) {
            return this._dueOffset === days;
        },
        syncDueFromInvoiceDate() {
            if (this._dueOffset) {
                this.setDueDays(this._dueOffset);
            }
        },
        validateBeforeSubmit(e) {
            const valid = this.lines.some(function (l) {
                return (l.description || '').trim().length > 0 && parseFloat(l.amount) >= 0;
            });
            if (!valid) {
                e.preventDefault();
                alert('Add at least one line item with a description and amount.');
            }
        },
    };
}
</script>

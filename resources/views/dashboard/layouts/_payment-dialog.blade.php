{{-- Record a payment without leaving the dashboard. Opened by [data-dash-action="pay"]. --}}
@if(auth()->user()->canAccessModule('payments') && (auth()->user()->isPartner() || auth()->user()->isManager()))
<dialog id="dash-pay-dialog" aria-labelledby="dash-pay-title"
    class="m-auto w-[28rem] max-w-[calc(100vw-2rem)] rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/40"
    data-store-url="{{ route('payments.store') }}">
    <form method="dialog" class="flex flex-col gap-4 p-5" data-pay-form>
        <div>
            <h2 id="dash-pay-title" class="text-lg font-bold text-slate-900">Record payment</h2>
            <p class="text-sm text-slate-600" data-pay-summary></p>
        </div>
        <input type="hidden" name="invoice_id">
        <div class="grid grid-cols-2 gap-3">
            <label class="col-span-2 flex flex-col gap-1 text-sm font-medium text-slate-700 sm:col-span-1">Amount (₹)
                <input type="number" name="amount" step="0.01" min="0.01" required class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-base focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            </label>
            <label class="col-span-2 flex flex-col gap-1 text-sm font-medium text-slate-700 sm:col-span-1">Date received
                <input type="date" name="payment_date" required value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-base focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            </label>
            <label class="col-span-2 flex flex-col gap-1 text-sm font-medium text-slate-700 sm:col-span-1">Mode
                <select name="payment_mode" required class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-base focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    <option value="Bank Transfer">Bank transfer / NEFT</option>
                    <option value="UPI">UPI</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Cash">Cash</option>
                    <option value="Online">Online</option>
                </select>
            </label>
            <label class="col-span-2 flex flex-col gap-1 text-sm font-medium text-slate-700 sm:col-span-1">Reference <span class="font-normal text-slate-500">(optional)</span>
                <input type="text" name="reference_number" maxlength="100" placeholder="UTR / cheque no." class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-base focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            </label>
        </div>
        <p class="hidden rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" data-pay-error role="alert"></p>
        <div class="flex justify-end gap-2">
            <button type="button" data-pay-cancel class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm text-slate-700">Cancel</button>
            <button type="submit" data-pay-submit class="min-h-11 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">Save payment</button>
        </div>
    </form>
</dialog>
@endif

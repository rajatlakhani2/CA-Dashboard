{{-- Shared add/edit fields for LIC, insurance, loans. Expects optional $renewal. --}}
@php
    $r = $renewal ?? null;
    $field = 'mt-1 block w-full rounded-md border-line bg-bg-body text-text-main shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm';
    $cat = old('category', $r?->category ?? request('category', 'LIC'));
@endphp
<div x-data="{ category: @js($cat) }" class="space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label for="category" class="block text-sm font-medium text-text-main">Type</label>
            <select name="category" id="category" x-model="category" required class="{{ $field }}">
                @foreach(\App\Models\PersonalRenewal::CATEGORIES as $value => $label)
                <option value="{{ $value }}" @selected($cat === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="sm:col-span-2">
            <label for="title" class="block text-sm font-medium text-text-main">Title</label>
            <input type="text" name="title" id="title" required value="{{ old('title', $r?->title) }}" placeholder="e.g. LIC Jeevan Anand – Papa" class="{{ $field }}">
        </div>

        <template x-if="category !== 'Loan' && category !== 'Other'">
            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="insurer" class="block text-sm font-medium text-text-main">Insurance company</label>
                    <input type="text" name="insurer" id="insurer" value="{{ old('insurer', $r?->insurer) }}" placeholder="LIC, Star Health, HDFC Ergo…" class="{{ $field }}">
                </div>
                <div>
                    <label for="policy_number" class="block text-sm font-medium text-text-main">Policy number</label>
                    <input type="text" name="policy_number" id="policy_number" value="{{ old('policy_number', $r?->policy_number) }}" class="{{ $field }}">
                </div>
                <div>
                    <label for="insured_person" class="block text-sm font-medium text-text-main">Family member / insured</label>
                    <input type="text" name="insured_person" id="insured_person" value="{{ old('insured_person', $r?->insured_person) }}" placeholder="Self, Mummy, Papa…" class="{{ $field }}">
                </div>
                <div>
                    <label for="sum_assured" class="block text-sm font-medium text-text-main">Sum assured (₹)</label>
                    <input type="number" step="1" min="0" name="sum_assured" id="sum_assured" value="{{ old('sum_assured', $r?->sum_assured) }}" class="{{ $field }}">
                </div>
                <div>
                    <label for="maturity_date" class="block text-sm font-medium text-text-main">Maturity / policy end date</label>
                    <input type="date" name="maturity_date" id="maturity_date" value="{{ old('maturity_date', $r?->maturity_date?->format('Y-m-d')) }}" class="{{ $field }}">
                </div>
            </div>
        </template>

        <div>
            <label for="amount" class="block text-sm font-medium text-text-main">Premium / amount (₹)</label>
            <input type="number" name="amount" id="amount" step="0.01" min="0" required value="{{ old('amount', $r?->amount) }}" class="{{ $field }}">
        </div>

        <div>
            <label for="due_date" class="block text-sm font-medium text-text-main">Next due date</label>
            <input type="date" name="due_date" id="due_date" required value="{{ old('due_date', $r?->due_date?->format('Y-m-d') ?? request('due_date')) }}" class="{{ $field }}">
        </div>

        <div>
            <label for="frequency" class="block text-sm font-medium text-text-main">Premium frequency</label>
            <select name="frequency" id="frequency" class="{{ $field }}">
                <option value="">One time</option>
                @foreach(\App\Models\PersonalRenewal::FREQUENCIES as $f)
                <option value="{{ $f }}" @selected(old('frequency', $r?->frequency ?? 'Yearly') === $f)>{{ $f }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-text-secondary">When marked paid, the next due date is created automatically.</p>
        </div>

        <div>
            <label for="remind_days_before" class="block text-sm font-medium text-text-main">Remind me</label>
            <select name="remind_days_before" id="remind_days_before" class="{{ $field }}">
                @foreach([3, 7, 15, 30] as $d)
                <option value="{{ $d }}" @selected((int) old('remind_days_before', $r?->remind_days_before ?? 7) === $d)>{{ $d }} days before</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-text-secondary">Also reminded 1 day before, on the due date, and while overdue.</p>
        </div>

        <div class="sm:col-span-2">
            <input type="hidden" name="whatsapp_reminder" value="0">
            <label class="inline-flex items-center gap-2 text-sm text-text-main">
                <input type="checkbox" name="whatsapp_reminder" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('whatsapp_reminder', $r?->whatsapp_reminder ?? true))>
                Send WhatsApp reminder to my mobile
            </label>
        </div>

        @if($r)
        <div>
            <label for="status" class="block text-sm font-medium text-text-main">Status</label>
            <select name="status" id="status" class="{{ $field }}">
                @foreach(['Pending', 'Paid'] as $status)
                <option value="{{ $status }}" @selected(old('status', $r->status) === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="sm:col-span-2">
            <label for="notes" class="block text-sm font-medium text-text-main">Notes</label>
            <textarea name="notes" id="notes" rows="3" class="{{ $field }}">{{ old('notes', $r?->notes) }}</textarea>
        </div>

        <div class="sm:col-span-2">
            <label for="document" class="block text-sm font-medium text-text-main">Policy document (optional)</label>
            <input type="file" name="document" id="document" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm text-text-secondary">
            @if($r?->document_path)
            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($r->document_path) }}" target="_blank" class="mt-1 inline-block text-xs text-indigo-600 underline">View current document</a>
            @endif
        </div>
    </div>
</div>

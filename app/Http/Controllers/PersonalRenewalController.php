<?php

namespace App\Http\Controllers;

use App\Models\PersonalRenewal;
use Illuminate\Http\Request;

class PersonalRenewalController extends Controller
{
    public function index(Request $request)
    {
        $query = PersonalRenewal::where('user_id', auth()->id());

        if ($request->tab === 'Insurance') {
            $query->whereIn('category', PersonalRenewal::INSURANCE_CATEGORIES);
        } elseif ($request->filled('tab') && $request->tab !== 'All') {
            $query->where('category', $request->tab);
        }

        $renewals = $query->orderBy('due_date', 'asc')->get();

        $pendingAll = PersonalRenewal::where('user_id', auth()->id())
            ->where('status', PersonalRenewal::STATUS_PENDING)
            ->get();

        $categoryCounts = [
            'All' => $pendingAll->count(),
            'LIC' => $pendingAll->where('category', 'LIC')->count(),
            'Insurance' => $pendingAll->whereIn('category', PersonalRenewal::INSURANCE_CATEGORIES)->count(),
            'Loan' => $pendingAll->where('category', 'Loan')->count(),
            'Other' => $pendingAll->where('category', 'Other')->count(),
        ];

        $events = $renewals->map(function ($renewal) {
            $clientName = $renewal->client?->name ?? 'Personal Renewal';
            $color = $renewal->status === PersonalRenewal::STATUS_PAID ? '#22c55e' : '#ef4444';

            return [
                'id' => 'renewal_' . $renewal->id,
                'title' => $renewal->title . ' (' . $renewal->amount . ')',
                'start' => $renewal->due_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'editable' => $renewal->status !== PersonalRenewal::STATUS_PAID,
                'extendedProps' => [
                    'type' => 'renewal',
                    'db_id' => $renewal->id,
                    'status' => $renewal->status,
                    'client_name' => $clientName,
                    'details' => $renewal->categoryLabel() . ' • ₹' . number_format($renewal->amount, 2),
                    'title_text' => $renewal->title,
                ],
            ];
        })->values()->all();

        return view('personal-renewals.index', compact('renewals', 'events', 'categoryCounts'));
    }

    public function create()
    {
        return view('personal-renewals.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            ...$this->policyRules(),
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = PersonalRenewal::STATUS_PENDING;

        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('personal_renewals', 'public');
            $validated['document_path'] = $path;
        }

        PersonalRenewal::create($validated);

        return redirect()
            ->route('personal-renewals.index')
            ->with('success', 'Renewal added successfully.');
    }

    public function edit(PersonalRenewal $personalRenewal)
    {
        $this->ensureOwner($personalRenewal);

        return view('personal-renewals.edit', compact('personalRenewal'));
    }

    public function update(Request $request, PersonalRenewal $personalRenewal)
    {
        $this->ensureOwner($personalRenewal);

        $validated = $request->validate([
            ...$this->policyRules(),
            'status' => 'required|in:' . implode(',', [PersonalRenewal::STATUS_PENDING, PersonalRenewal::STATUS_PAID]),
        ]);

        if ($request->hasFile('document')) {
            // Delete old file if exists
            if ($personalRenewal->document_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($personalRenewal->document_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($personalRenewal->document_path);
            }
            $path = $request->file('document')->store('personal_renewals', 'public');
            $validated['document_path'] = $path;
        }

        $originalStatus = $personalRenewal->status;
        $personalRenewal->update($validated);

        // Auto-create next renewal if recurring and marked as Paid
        if ($originalStatus !== PersonalRenewal::STATUS_PAID && $validated['status'] === PersonalRenewal::STATUS_PAID && !empty($personalRenewal->frequency)) {
            $nextDate = $personalRenewal->due_date->copy();

            switch ($personalRenewal->frequency) {
                case 'Monthly':
                    $nextDate->addMonth();
                    break;
                case 'Quarterly':
                    $nextDate->addQuarter();
                    break;
                case 'Half-Yearly':
                    $nextDate->addMonths(6);
                    break;
                case 'Yearly':
                    $nextDate->addYear();
                    break;
            }

            PersonalRenewal::create([
                'user_id' => $personalRenewal->user_id,
                'client_id' => $personalRenewal->client_id, // Carry over client
                'title' => $personalRenewal->title,
                'category' => $personalRenewal->category,
                'amount' => $personalRenewal->amount,
                'frequency' => $personalRenewal->frequency,
                'policy_number' => $personalRenewal->policy_number,
                'insurer' => $personalRenewal->insurer,
                'insured_person' => $personalRenewal->insured_person,
                'sum_assured' => $personalRenewal->sum_assured,
                'maturity_date' => $personalRenewal->maturity_date,
                'remind_days_before' => $personalRenewal->remind_days_before,
                'whatsapp_reminder' => $personalRenewal->whatsapp_reminder,
                'due_date' => $nextDate,
                'status' => PersonalRenewal::STATUS_PENDING,
                'notes' => 'Auto-generated renewal',
                // Don't carry over document path for new period usually? Or maybe yes? Let's say no for now.
            ]);

            return redirect()
                ->route('personal-renewals.index')
                ->with('success', 'Renewal marked paid & next cycle created!');
        }

        return redirect()
            ->route('personal-renewals.index', request()->only('tab'))
            ->with('success', 'Renewal updated successfully.');
    }

    public function destroy(PersonalRenewal $personalRenewal)
    {
        $this->ensureOwner($personalRenewal);

        if ($personalRenewal->document_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($personalRenewal->document_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($personalRenewal->document_path);
        }

        $personalRenewal->delete();

        return redirect()
            ->route('personal-renewals.index', request()->only('tab'))
            ->with('success', 'Renewal deleted successfully.');
    }

    public function sendWhatsApp(PersonalRenewal $personalRenewal, \App\Services\WhatsAppService $whatsapp)
    {
        $this->ensureOwner($personalRenewal);

        // Use the new template
        $template = $whatsapp->getTemplates()['personal_renewal'];

        $message = str_replace(
            ['{category}', '{title}', '{amount}', '{due_date}'],
            [$personalRenewal->category, $personalRenewal->title, number_format($personalRenewal->amount), $personalRenewal->due_date->format('d M Y')],
            $template
        );

        // Dummy mobile number (User's phone)
        $mobile = auth()->user()->mobile;

        $whatsapp->sendMessage($mobile, $message);

        return back()->with('success', 'WhatsApp reminder sent successfully!');
    }

    private function policyRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'category' => 'required|in:'.implode(',', array_keys(PersonalRenewal::CATEGORIES)),
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'nullable|in:'.implode(',', PersonalRenewal::FREQUENCIES),
            'notes' => 'nullable|string',
            'policy_number' => 'nullable|string|max:100',
            'insurer' => 'nullable|string|max:150',
            'insured_person' => 'nullable|string|max:150',
            'sum_assured' => 'nullable|numeric|min:0',
            'maturity_date' => 'nullable|date',
            'remind_days_before' => 'nullable|integer|in:3,7,15,30',
            'whatsapp_reminder' => 'nullable|boolean',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    /** Personal renewals are private to the person who added them. */
    private function ensureOwner(PersonalRenewal $renewal): void
    {
        abort_unless((int) $renewal->user_id === (int) auth()->id(), 403);
    }
}

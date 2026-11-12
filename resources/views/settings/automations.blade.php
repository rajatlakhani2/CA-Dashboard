@extends('layouts.app')

@section('header', 'Automations')

@php
    $types = [
        'morning_brief' => 'Morning brief',
        'payment_reminder' => 'Payment reminder',
        'document_request' => 'Document request',
        'weekly_summary' => 'Weekly summary',
    ];
    $hour = fn ($h) => \Carbon\Carbon::createFromTime((int) $h)->format('g A');
@endphp

@section('content')
<div class="mx-auto flex w-full max-w-5xl flex-col gap-5" data-page="automations">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('settings.index') }}" class="text-sm text-indigo-700 underline">← Settings</a>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Automations</h1>
            <p class="mt-1 text-sm text-slate-600">Messages the app sends for you. Everything starts switched off. Times are India time. Each message is sent once only.</p>
        </div>
    </div>

    @unless($whatsappReady)
    <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        <strong>WhatsApp isn’t connected yet.</strong> WhatsApp messages will be logged as failed until the Meta WhatsApp token and phone number ID are added to the server’s <code>.env</code>.
    </div>
    @endunless
    @unless($mailReady)
    <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        <strong>Email isn’t connected yet.</strong> The server is set to log emails instead of sending them. Set the <code>MAIL_*</code> values in <code>.env</code> to your cPanel email account so emails actually go out.
    </div>
    @endunless

    <form method="POST" action="{{ route('settings.automations.update') }}" class="flex flex-col gap-4">
        @csrf @method('PUT')

        {{-- WhatsApp limit --}}
        @php $limitOn = $settings['whatsapp_only_to'] !== ''; @endphp
        <section class="rounded-2xl border-2 {{ $limitOn ? 'border-indigo-300 bg-indigo-50/60' : 'border-slate-200 bg-white' }} p-5" x-data="{ on: {{ $limitOn ? 'true' : 'false' }} }">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-slate-900">Who can receive automatic WhatsApp</h2>
                    <p class="mt-1 text-sm text-slate-600">While this is on, automatic WhatsApp messages (morning briefs, client reminders, the old task reminders and new-task alerts) go <strong>only</strong> to the numbers below. Staff and clients get nothing on WhatsApp. Emails are not affected. WhatsApp you send yourself with a button still works.</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <input type="checkbox" name="whatsapp_limit" value="1" x-model="on" class="h-5 w-5 rounded border-slate-300 text-indigo-600"> Only these numbers
                </label>
            </div>
            <label class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-700" x-show="on">Numbers
                <input type="text" name="whatsapp_only_to" value="{{ old('whatsapp_only_to', $limitOn ? $settings['whatsapp_only_to'] : auth()->user()->mobile) }}"
                    placeholder="919876543210" class="min-h-10 w-72 max-w-full rounded-lg border border-slate-300 bg-white px-3">
                <span class="text-slate-500">with country code, comma-separated</span>
            </label>
            @error('whatsapp_only_to')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
        </section>

        {{-- Morning brief --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5" x-data="{ on: {{ $settings['auto_morning_brief'] === '1' ? 'true' : 'false' }} }">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-slate-900">Morning WhatsApp to each staff member</h2>
                    <p class="mt-1 text-sm text-slate-600">Their overdue items and what’s due today, from the Action inbox. Nobody gets a message on a day with nothing to do. When this is on, it replaces the old morning task reminder.</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <input type="checkbox" name="auto_morning_brief" value="1" x-model="on" class="h-5 w-5 rounded border-slate-300 text-indigo-600"> On
                </label>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-700">
                <label class="flex items-center gap-2">Send at
                    <select name="auto_morning_brief_hour" class="min-h-10 rounded-lg border border-slate-300 bg-white px-2">
                        @for($h = 5; $h <= 12; $h++)<option value="{{ $h }}" @selected((int) $settings['auto_morning_brief_hour'] === $h)>{{ $hour($h) }}</option>@endfor
                    </select>
                </label>
            </div>
            <details class="mt-3">
                <summary class="cursor-pointer text-sm font-medium text-indigo-700">Preview your own brief for today</summary>
                <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-slate-50 p-4 font-sans text-sm text-slate-800">{{ $preview['morning_brief'] }}</pre>
            </details>
        </section>

        {{-- Payment reminders --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-slate-900">Payment reminders to clients</h2>
                    <p class="mt-1 text-sm text-slate-600">WhatsApp and email to the client when an unpaid invoice reaches each of these days past its due date. One message per stage.</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <input type="checkbox" name="auto_payment_reminders" value="1" @checked($settings['auto_payment_reminders'] === '1') class="h-5 w-5 rounded border-slate-300 text-indigo-600"> On
                </label>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-700">
                <label class="flex items-center gap-2">Days overdue
                    <input type="text" name="auto_payment_reminder_days" value="{{ old('auto_payment_reminder_days', $settings['auto_payment_reminder_days']) }}" class="min-h-10 w-32 rounded-lg border border-slate-300 bg-white px-3" aria-describedby="days-help">
                </label>
                <span id="days-help" class="text-slate-500">e.g. 7,15,30</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ $counts['payment_reminder'] }} invoice(s) would get a reminder now</span>
            </div>
            @error('auto_payment_reminder_days')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            @if($preview['payment_reminder'])
            <details class="mt-3">
                <summary class="cursor-pointer text-sm font-medium text-indigo-700">Preview a reminder</summary>
                <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-slate-50 p-4 font-sans text-sm text-slate-800">{{ $preview['payment_reminder'] }}</pre>
            </details>
            @endif
        </section>

        {{-- Document requests --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-slate-900">Document requests to clients</h2>
                    <p class="mt-1 text-sm text-slate-600">Before a filing is due, ask the client for the documents still missing from that service’s checklist.</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <input type="checkbox" name="auto_document_requests" value="1" @checked($settings['auto_document_requests'] === '1') class="h-5 w-5 rounded border-slate-300 text-indigo-600"> On
                </label>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-700">
                <label class="flex items-center gap-2">Days before the due date
                    <input type="number" name="auto_document_request_days" min="1" max="30" value="{{ old('auto_document_request_days', $settings['auto_document_request_days']) }}" class="min-h-10 w-20 rounded-lg border border-slate-300 bg-white px-3">
                </label>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ $counts['document_request'] }} request(s) would go out today</span>
            </div>
            @if($preview['document_request'])
            <details class="mt-3">
                <summary class="cursor-pointer text-sm font-medium text-indigo-700">Preview a request</summary>
                <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-slate-50 p-4 font-sans text-sm text-slate-800">{{ $preview['document_request'] }}</pre>
            </details>
            @endif
            <p class="mt-3 text-sm text-slate-600">Client messages go out at
                <select name="auto_client_hour" class="min-h-9 rounded-lg border border-slate-300 bg-white px-2" aria-label="Time for client messages">
                    @for($h = 9; $h <= 19; $h++)<option value="{{ $h }}" @selected((int) $settings['auto_client_hour'] === $h)>{{ $hour($h) }}</option>@endfor
                </select>
                every day, for both payment reminders and document requests.</p>
        </section>

        {{-- Weekly summary --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-bold text-slate-900">Weekly summary email to partners</h2>
                    <p class="mt-1 text-sm text-slate-600">Every Monday: filings done and overdue, money billed, collected and outstanding, biggest overdue clients, and who is overloaded.</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <input type="checkbox" name="auto_weekly_summary" value="1" @checked($settings['auto_weekly_summary'] === '1') class="h-5 w-5 rounded border-slate-300 text-indigo-600"> On
                </label>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-700">
                <label class="flex items-center gap-2">Monday at
                    <select name="auto_weekly_summary_hour" class="min-h-10 rounded-lg border border-slate-300 bg-white px-2">
                        @for($h = 6; $h <= 12; $h++)<option value="{{ $h }}" @selected((int) $settings['auto_weekly_summary_hour'] === $h)>{{ $hour($h) }}</option>@endfor
                    </select>
                </label>
            </div>
            @if($preview['weekly_summary'])
            <details class="mt-3">
                <summary class="cursor-pointer text-sm font-medium text-indigo-700">Preview this week’s summary</summary>
                <pre class="mt-2 whitespace-pre-wrap rounded-xl bg-slate-50 p-4 font-sans text-sm text-slate-800">{{ $preview['weekly_summary'] }}</pre>
            </details>
            @endif
        </section>

        <div class="flex justify-end">
            <button type="submit" class="min-h-11 rounded-xl bg-indigo-600 px-6 text-sm font-semibold text-white hover:bg-indigo-700">Save automations</button>
        </div>
    </form>

    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Send yourself a test</h2>
                <p class="mt-1 text-sm text-slate-600">Sends today’s morning brief to your own WhatsApp ({{ auth()->user()->mobile ?: 'no mobile saved' }}).</p>
            </div>
            <form method="POST" action="{{ route('settings.automations.test-brief') }}">
                @csrf
                <button type="submit" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:border-slate-400">Send test brief</button>
            </form>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-bold text-slate-900">Clients who don’t get automatic messages</h2>
        <p class="mt-1 text-sm text-slate-600">Turn reminders off for a client from the <strong>Client first</strong> dashboard layout.</p>
        @forelse($optedOut as $c)
            <span class="mr-2 mt-2 inline-block rounded-full bg-slate-100 px-3 py-1 text-sm text-slate-800">{{ $c->name }}</span>
        @empty
            <p class="mt-2 text-sm text-slate-600">None – all clients can receive automatic messages.</p>
        @endforelse
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-bold text-slate-900">Recent automatic messages</h2>
        @if($log->isEmpty())
            <p class="mt-2 text-sm text-slate-600">Nothing sent yet.</p>
        @else
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wide text-slate-600">
                    <tr><th class="py-2 pr-4">When</th><th class="py-2 pr-4">Type</th><th class="py-2 pr-4">To</th><th class="py-2 pr-4">About</th><th class="py-2">Result</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($log as $l)
                    <tr>
                        <td class="py-2 pr-4 whitespace-nowrap text-slate-700">{{ $l->updated_at->timezone(\App\Support\AutomationSettings::TIMEZONE)->format('d M, g:i A') }}</td>
                        <td class="py-2 pr-4 text-slate-900">{{ $types[$l->type] ?? $l->type }} · {{ $l->channel === 'email' ? 'Email' : 'WhatsApp' }}</td>
                        <td class="py-2 pr-4 text-slate-700">{{ $l->recipient }}</td>
                        <td class="py-2 pr-4 text-slate-700">{{ $l->subject_label }}</td>
                        <td class="py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $l->status === 'sent' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-700' }}" title="{{ $l->detail }}">{{ $l->status === 'sent' ? 'Sent' : 'Failed' }}</span>
                            @if($l->detail)<span class="block text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($l->detail, 90) }}</span>@endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </section>
</div>
@endsection

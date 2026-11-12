<?php

namespace App\Http\Controllers;

use App\Models\AutomationLog;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Setting;
use App\Services\AutomationService;
use App\Support\AutomationSettings;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    public function index(Request $request, AutomationService $automation)
    {
        $this->authorize('updateFirm', Setting::class);
        $user = $request->user();

        $payments = $automation->paymentRemindersDue();
        $docs = $automation->documentRequestsDue();
        $org = Organization::find($user->organization_id);

        $samplePayment = $payments->first();
        $sampleDocs = $docs->first();

        return view('settings.automations', [
            'settings' => AutomationSettings::all(),
            'whatsappReady' => filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'))
                && ! str_contains((string) config('services.whatsapp.token'), 'your_meta_app'),
            'mailReady' => ! in_array(config('mail.default'), ['log', 'array', null], true),
            'preview' => [
                'morning_brief' => $automation->morningBriefFor($user)['text'],
                'payment_reminder' => $samplePayment
                    ? $automation->paymentReminderText($samplePayment['invoice'], $samplePayment['balance'], $samplePayment['days'])
                    : null,
                'document_request' => $sampleDocs
                    ? $automation->documentRequestText($sampleDocs['client'], $sampleDocs['service'], $sampleDocs['due']->due_date, $sampleDocs['missing'])
                    : null,
                'weekly_summary' => $org ? $automation->weeklySummary($org)['text'] : null,
            ],
            'counts' => [
                'payment_reminder' => $payments->count(),
                'document_request' => $docs->count(),
            ],
            'optedOut' => Client::query()->where('auto_reminders', false)->orderBy('name')->get(['id', 'name']),
            'log' => AutomationLog::query()->latest()->limit(25)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('updateFirm', Setting::class);

        $data = $request->validate([
            'auto_morning_brief' => ['nullable', 'boolean'],
            'auto_morning_brief_hour' => ['required', 'integer', 'between:5,12'],
            'auto_payment_reminders' => ['nullable', 'boolean'],
            'auto_payment_reminder_days' => ['required', 'string', 'max:40', 'regex:/^\s*\d{1,3}(\s*,\s*\d{1,3})*\s*$/'],
            'auto_document_requests' => ['nullable', 'boolean'],
            'auto_document_request_days' => ['required', 'integer', 'between:1,30'],
            'auto_client_hour' => ['required', 'integer', 'between:9,19'],
            'auto_weekly_summary' => ['nullable', 'boolean'],
            'auto_weekly_summary_hour' => ['required', 'integer', 'between:6,12'],
            'whatsapp_limit' => ['nullable', 'boolean'],
            'whatsapp_only_to' => ['nullable', 'string', 'max:200'],
        ], [
            'auto_payment_reminder_days.regex' => 'Enter days as numbers separated by commas, e.g. 7,15,30.',
        ]);

        foreach (['auto_morning_brief', 'auto_payment_reminders', 'auto_document_requests', 'auto_weekly_summary'] as $flag) {
            $data[$flag] = $request->boolean($flag) ? '1' : '0';
        }
        $data['auto_payment_reminder_days'] = preg_replace('/\s+/', '', $data['auto_payment_reminder_days']);

        $numbers = collect(explode(',', (string) ($data['whatsapp_only_to'] ?? '')))
            ->map(fn ($n) => \App\Services\WhatsAppService::normalise($n))->filter()->unique()->implode(',');
        if ($request->boolean('whatsapp_limit') && $numbers === '') {
            return back()->withInput()->withErrors(['whatsapp_only_to' => 'Enter at least one mobile number with country code.']);
        }
        $data['whatsapp_only_to'] = $request->boolean('whatsapp_limit') ? $numbers : '';
        unset($data['whatsapp_limit']);

        AutomationSettings::save($data);

        return redirect()->route('settings.automations')->with('success', 'Automation settings saved.');
    }

    /** Send the morning brief to the signed-in user right now (to check it arrives). */
    public function testBrief(Request $request, AutomationService $automation)
    {
        $this->authorize('updateFirm', Setting::class);
        $user = $request->user();

        if (! $user->mobile) {
            return back()->with('error', 'Add your mobile number in Settings → Profile first.');
        }

        $org = Organization::findOrFail($user->organization_id);
        $result = $automation->deliver($org, 'morning_brief', 'test:'.$user->id.':'.now()->timestamp, 'whatsapp',
            $user->mobile, $user->name.' (test)', $automation->morningBriefFor($user)['text']);

        return back()->with($result === 'sent' ? 'success' : 'error',
            $result === 'sent' ? 'Test brief sent to '.$user->mobile.'.' : 'WhatsApp could not send the test. Check the log below for the reason.');
    }

    /** Turn automatic client reminders on or off for one client. */
    public function toggleClient(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $client->auto_reminders = $request->boolean('auto_reminders');
        $client->save();

        return back()->with('success', $client->auto_reminders
            ? "Automatic reminders turned on for {$client->name}."
            : "Automatic reminders turned off for {$client->name}.");
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use App\Models\Client;
use App\Models\Setting;

class WhatsAppController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function index()
    {
        $templates = $this->whatsapp->getTemplates();
        $clients = Client::where('status', Client::STATUS_ACTIVE)->limit(50)->get(); // For testing
        
        try {
            $time1 = Setting::get('reminder_time_1', '10:00');
            $time2 = Setting::get('reminder_time_2', '18:00');
            $daysAhead = Setting::get('reminder_days_ahead', '7');
        } catch (\Exception $e) {
            $time1 = '10:00';
            $time2 = '18:00';
            $daysAhead = '7';
        }

        $webhookUrl = url('/webhooks/whatsapp');
        $inboundEnabled = (bool) config('whatsapp.inbound_enabled');

        return view('notifications.whatsapp', compact(
            'templates',
            'clients',
            'time1',
            'time2',
            'daysAhead',
            'webhookUrl',
            'inboundEnabled',
        ));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'reminder_time_1' => 'required',
            'reminder_time_2' => 'required',
            'reminder_days_ahead' => 'required|integer|min:1|max:365',
        ]);

        try {
            Setting::set('reminder_time_1', $request->reminder_time_1);
            Setting::set('reminder_time_2', $request->reminder_time_2);
            Setting::set('reminder_days_ahead', $request->reminder_days_ahead);
            return back()->with('success', 'Notification settings saved successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save settings. Make sure settings table exists.');
        }
    }

    public function sendTest(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'template_key' => 'required',
            'custom_message' => 'nullable|string'
        ]);

        $client = Client::find($request->client_id);
        $templateKey = $request->template_key;
        $templates = $this->whatsapp->getTemplates();

        if (isset($templates[$templateKey])) {
            $message = $templates[$templateKey];

            // Basic replacement logic
            $replacements = [
                '{client_name}' => $client->name,
                '{amount}' => '10,000', // Dummy
                '{invoice_number}' => 'INV-001', // Dummy
                '{due_date}' => now()->addDays(5)->format('d M Y'),
                '{service_name}' => 'GST Return',
                '{deadline}' => now()->addDays(2)->format('d M Y'),
            ];

            foreach ($replacements as $key => $val) {
                $message = str_replace($key, $val, $message);
            }
        } else {
            $message = $request->custom_message ?? 'Test Message';
        }

        // Use the service to send
        // Assuming client has a mobile_number field. If not, use dummy.
        $mobile = $client->mobile_number ?? '919999999999';

        $result = $this->whatsapp->sendMessage($mobile, $message);

        if ($result['success']) {
            return back()->with('success', "Message successfully sent to {$client->name} ({$mobile})");
        } else {
            return back()->with('error', "Failed to send: " . $result['message']);
        }
    }
}

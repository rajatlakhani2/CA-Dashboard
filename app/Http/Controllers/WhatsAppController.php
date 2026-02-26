<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use App\Models\Client;

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
        $clients = Client::where('status', 'Active')->limit(50)->get(); // For testing
        return view('notifications.whatsapp', compact('templates', 'clients'));
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

        return back()->with('success', "Message sent to {$client->name} ({$mobile}) [Simulated]");
    }
}

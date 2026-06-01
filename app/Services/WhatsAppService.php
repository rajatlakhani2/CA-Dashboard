<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiUrl;
    protected $apiToken;
    protected $instanceId;

    public function __construct()
    {
        $this->apiToken = config('services.whatsapp.token');
        $this->instanceId = config('services.whatsapp.phone_number_id'); // Storing phone_number_id
        $this->apiUrl = "https://graph.facebook.com/v18.0/{$this->instanceId}/messages";
    }

    /**
     * Send a WhatsApp message to a specific number.
     *
     * @param string $mobile Number with country code (e.g. 919876543210)
     * @param string $message Text message content
     * @return array
     */
    public function sendMessage($mobile, $message)
    {
        if (empty($this->apiToken) || empty($this->instanceId) || str_contains($this->apiToken, 'your_meta_app')) {
            Log::warning("WhatsApp API not configured. Missing token or phone number ID.");
            return [
                'success' => false,
                'message' => 'WhatsApp API not configured. Please check your .env file.',
            ];
        }

        try {
            $response = Http::withToken($this->apiToken)->withOptions(['verify' => false])->post($this->apiUrl, [
                'messaging_product' => 'whatsapp',
                'to' => $mobile,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message
                ]
            ]);

            if ($response->successful()) {
                Log::info("WhatsApp Message sent to {$mobile}");
                return [
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => $response->json()
                ];
            }

            Log::error("WhatsApp Send Error: " . $response->body());
            return [
                'success' => false,
                'message' => 'Failed to send message: ' . $response->json('error.message', 'Unknown error'),
            ];

        } catch (\Exception $e) {
            Log::error("WhatsApp Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get predefined templates (Hardcoded for now).
     */
    public function getTemplates()
    {
        return [
            'payment_reminder' => "Dear {client_name}, this is a gentle reminder that your payment of ₹{amount} for Invoice {invoice_number} is due on {due_date}. Please pay at the earliest.",
            'service_due' => "Dear {client_name}, your {service_name} is due on {due_date}. Please submit necessary documents by {deadline}.",
            'birthday' => "Happy Birthday {client_name}! Wishing you a fantastic year ahead from Team CA Dashboard.",
            'personal_renewal' => "Reminder: Your {category} ({title}) of ₹{amount} is due on {due_date}. Please ensure sufficient balance.",
        ];
    }
}

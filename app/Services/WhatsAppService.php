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
        // Example config structure - in real app, put in services.php
        $this->apiUrl = config('services.whatsapp.url', 'https://api.example.com');
        $this->apiToken = config('services.whatsapp.token', 'dummy_token');
        $this->instanceId = config('services.whatsapp.instance_id', 'dummy_instance');
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
        // Simulation Mode (since we don't have a real API key)
        Log::info("WhatsApp Simulated Send to {$mobile}: {$message}");

        return [
            'success' => true,
            'message' => 'Message queued (simulated)',
            'data' => [
                'to' => $mobile,
                'body' => $message,
                'status' => 'queued'
            ]
        ];

        /* Real Implementation Example:
        $response = Http::post("{$this->apiUrl}/message/send", [
            'token' => $this->apiToken,
            'to' => $mobile,
            'body' => $message
        ]);
        return $response->json();
        */
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

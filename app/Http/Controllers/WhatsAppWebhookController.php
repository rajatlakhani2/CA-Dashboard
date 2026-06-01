<?php

namespace App\Http\Controllers;

use App\Services\Intelligence\WhatsAppInboundBot;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $expected = config('whatsapp.webhook_verify_token');

        if ($mode === 'subscribe' && $expected && hash_equals($expected, (string) $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        abort(403, 'Invalid verify token');
    }

    public function handle(Request $request, WhatsAppInboundBot $bot): Response
    {
        if (! $bot->isEnabled()) {
            return response('disabled', 200);
        }

        $payload = $request->all();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $message) {
                    if (($message['type'] ?? '') !== 'text') {
                        continue;
                    }

                    $from = $message['from'] ?? null;
                    $body = $message['text']['body'] ?? '';

                    if (! $from || $body === '') {
                        continue;
                    }

                    try {
                        $bot->handleIncoming($from, $body, [
                            'message_id' => $message['id'] ?? null,
                            'timestamp' => $message['timestamp'] ?? null,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('WhatsApp inbound error: ' . $e->getMessage(), [
                            'from' => $from,
                        ]);
                    }
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\Intelligence\AiAssistantService;
use Illuminate\Http\Request;

class ClientAiController extends Controller
{
    public function summarize(Client $client, AiAssistantService $ai)
    {
        $this->authorizeAi($client);

        $result = $ai->summarize($client);

        return $this->jsonResponse($result);
    }

    public function explainOverdue(Client $client, AiAssistantService $ai)
    {
        $this->authorizeAi($client);

        $result = $ai->explainOverdue($client);

        return $this->jsonResponse($result);
    }

    public function draftWhatsApp(Request $request, Client $client, AiAssistantService $ai)
    {
        $this->authorizeAi($client);

        $request->validate([
            'tone' => 'nullable|in:polite,firm,friendly',
        ]);

        $result = $ai->draftWhatsAppReminder($client, $request->input('tone', 'polite'));

        return $this->jsonResponse($result);
    }

    protected function authorizeAi(Client $client): void
    {
        $this->authorize('view', $client);
        abort_unless(auth()->user()?->managesFirmModules(), 403);
    }

    /**
     * @param  array{ok: bool, text: ?string, error: ?string}  $result
     */
    protected function jsonResponse(array $result)
    {
        return response()->json([
            'ok' => $result['ok'],
            'text' => $result['text'],
            'error' => $result['error'],
            'disclaimer' => config('ai.disclaimer'),
            'enabled' => app(AiAssistantService::class)->isEnabled(),
        ]);
    }
}

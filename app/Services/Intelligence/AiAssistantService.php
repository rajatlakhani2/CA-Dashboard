<?php

namespace App\Services\Intelligence;

use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    public function __construct(
        protected ClientContextBuilder $contextBuilder,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('ai.enabled')
            && filled(config('ai.openai.api_key'));
    }

    /**
     * @return array{ok: bool, text: ?string, error: ?string}
     */
    public function summarize(Client $client): array
    {
        $context = $this->contextBuilder->build($client);

        return $this->complete(
            'You are an operations assistant for a CA firm in India. Summarize the client snapshot in 5-8 bullet points. Cover compliance dues, tasks, and collections. Do not invent data. Do not give tax or legal advice.',
            "Summarize this client:\n" . $this->contextBuilder->toPromptText($context)
        );
    }

    /**
     * @return array{ok: bool, text: ?string, error: ?string}
     */
    public function explainOverdue(Client $client): array
    {
        $context = $this->contextBuilder->build($client);

        return $this->complete(
            'You explain overdue work for a CA firm operations team. List overdue/pending compliance dues, overdue invoices, and late tasks from the JSON only. Prioritize by urgency. Plain English. No tax advice.',
            "Explain the overdue stack:\n" . $this->contextBuilder->toPromptText($context)
        );
    }

    /**
     * @return array{ok: bool, text: ?string, error: ?string}
     */
    public function draftWhatsAppReminder(Client $client, string $tone = 'polite'): array
    {
        $context = $this->contextBuilder->build($client);

        return $this->complete(
            'Draft a short WhatsApp reminder message to the client primary contact. Use ₹ for amounts. Under 400 characters. Professional, ' . $tone . '. Mention specific open dues or invoices from JSON if present. End with firm sign-off placeholder [Firm Name]. No passwords or portal credentials.',
            "Draft WhatsApp reminder:\n" . $this->contextBuilder->toPromptText($context)
        );
    }

    /**
     * @return array{ok: bool, text: ?string, error: ?string}
     */
    protected function complete(string $systemPrompt, string $userPrompt): array
    {
        if (! $this->isEnabled()) {
            return [
                'ok' => false,
                'text' => null,
                'error' => 'AI assistant is disabled. Set AI_ENABLED=true and OPENAI_API_KEY in .env.',
            ];
        }

        try {
            $response = Http::withToken(config('ai.openai.api_key'))
                ->timeout(45)
                ->post(rtrim(config('ai.openai.base_url'), '/') . '/chat/completions', [
                    'model' => config('ai.openai.model'),
                    'max_tokens' => config('ai.max_tokens'),
                    'temperature' => 0.3,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('AI API error', ['body' => $response->body()]);

                return [
                    'ok' => false,
                    'text' => null,
                    'error' => 'AI service returned an error. Check API key and quota.',
                ];
            }

            $text = trim($response->json('choices.0.message.content', ''));

            return [
                'ok' => $text !== '',
                'text' => $text !== '' ? $text : null,
                'error' => $text !== '' ? null : 'Empty response from AI.',
            ];
        } catch (\Throwable $e) {
            Log::error('AI exception: ' . $e->getMessage());

            return [
                'ok' => false,
                'text' => null,
                'error' => 'Could not reach AI service.',
            ];
        }
    }
}

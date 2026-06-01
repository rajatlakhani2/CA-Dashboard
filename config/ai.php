<?php

return [

    'enabled' => env('AI_ENABLED', false),

    'provider' => env('AI_PROVIDER', 'openai'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    'max_tokens' => (int) env('AI_MAX_TOKENS', 800),

    'disclaimer' => 'AI-generated operational summary only — not tax or legal advice. Review before sending to clients.',

];

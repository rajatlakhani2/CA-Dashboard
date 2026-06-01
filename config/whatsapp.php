<?php

return [

    'inbound_enabled' => env('WHATSAPP_INBOUND_ENABLED', false),

    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),

    'firm_reply_name' => env('WHATSAPP_FIRM_REPLY_NAME', 'RLA Associates'),

    'handoff_phone' => env('WHATSAPP_HANDOFF_PHONE', ''),

];

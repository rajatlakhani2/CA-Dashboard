<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageLog extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    protected $table = 'whatsapp_message_logs';

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    protected $fillable = [
        'client_id',
        'phone',
        'direction',
        'body',
        'intent',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

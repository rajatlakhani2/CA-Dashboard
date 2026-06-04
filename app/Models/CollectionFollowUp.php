<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionFollowUp extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    public const CHANNEL_PHONE = 'phone';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_IN_PERSON = 'in_person';

    protected $fillable = [
        'client_id',
        'user_id',
        'channel',
        'notes',
        'promise_date',
        'next_action',
        'contacted_at',
    ];

    protected $casts = [
        'promise_date' => 'date',
        'contacted_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

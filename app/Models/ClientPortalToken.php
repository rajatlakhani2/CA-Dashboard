<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ClientPortalToken extends Model
{
    protected $fillable = [
        'client_id',
        'token_hash',
        'expires_at',
        'last_accessed_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * @return array{model: self, plain: string}
     */
    public static function issueForClient(Client $client, int $daysValid = 30): array
    {
        $plain = Str::random(48);

        $model = self::create([
            'client_id' => $client->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addDays($daysValid),
            'created_by' => auth()->id(),
        ]);

        return ['model' => $model, 'plain' => $plain];
    }

    public static function findValid(string $plain): ?self
    {
        $hash = hash('sha256', $plain);

        $token = self::query()
            ->where('token_hash', $hash)
            ->where('expires_at', '>', now())
            ->first();

        if ($token) {
            $token->forceFill(['last_accessed_at' => now()])->save();
        }

        return $token;
    }
}

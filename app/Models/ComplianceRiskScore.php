<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceRiskScore extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    public const LEVEL_LOW = 'low';

    public const LEVEL_MEDIUM = 'medium';

    public const LEVEL_HIGH = 'high';

    protected $fillable = [
        'client_id',
        'service_id',
        'score',
        'level',
        'predicted_miss',
        'model_version',
        'signals',
        'next_due_date',
        'fingerprint',
        'scored_at',
    ];

    protected $casts = [
        'signals' => 'array',
        'predicted_miss' => 'boolean',
        'next_due_date' => 'date',
        'scored_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('level', [self::LEVEL_HIGH, self::LEVEL_MEDIUM]);
    }
}

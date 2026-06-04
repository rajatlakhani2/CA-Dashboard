<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmAlert extends Model
{
    use \App\Models\Concerns\BelongsToOrganization;
    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    public const TYPE_DUPLICATE_PAN = 'duplicate_pan';

    public const TYPE_HIGH_OUTSTANDING = 'high_outstanding';

    public const TYPE_CREDENTIAL_IDLE = 'credential_idle';

    public const TYPE_COMPLIANCE_STACK = 'compliance_stack';

    protected $fillable = [
        'type',
        'severity',
        'title',
        'message',
        'client_id',
        'related_type',
        'related_id',
        'fingerprint',
        'metadata',
        'dismissed_at',
        'dismissed_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'dismissed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('dismissed_at');
    }

    public function dismiss(?User $user = null): void
    {
        $this->forceFill([
            'dismissed_at' => now(),
            'dismissed_by' => $user?->id ?? auth()->id(),
        ])->save();
    }
}

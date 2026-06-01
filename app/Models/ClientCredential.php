<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ClientCredential extends Model
{
    use HasFactory, LogsActivity;

    public const CATEGORY_GST = 'GST';

    public const CATEGORY_IT = 'IT';

    public const CATEGORY_MCA = 'MCA';

    public const CATEGORY_TAN = 'TAN';

    public const CATEGORY_BANK = 'Bank';

    public const CATEGORY_PF = 'PF';

    public const CATEGORY_ESIC = 'ESIC';

    public const CATEGORY_OTHER = 'Other';

    public const CATEGORIES = [
        self::CATEGORY_GST,
        self::CATEGORY_IT,
        self::CATEGORY_MCA,
        self::CATEGORY_TAN,
        self::CATEGORY_BANK,
        self::CATEGORY_PF,
        self::CATEGORY_ESIC,
        self::CATEGORY_OTHER,
    ];

    public const AUDIT_REVEALED_PASSWORD = 'revealed_password';

    public const AUDIT_REVEALED_USERNAME = 'revealed_username';

    public const AUDIT_COPIED_PASSWORD = 'copied_password';

    public const AUDIT_COPIED_USERNAME = 'copied_username';

    protected $fillable = [
        'client_id',
        'portal_name',
        'category',
        'username',
        'password',
        'notes',
        'last_accessed_at',
        'last_accessed_by',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'last_accessed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('credential_vault')
            ->logOnly(['client_id', 'portal_name', 'category', 'username', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'added credential vault entry for',
            'updated' => 'updated credential vault entry for',
            'deleted' => 'deleted credential vault entry for',
            default => $eventName,
        };
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lastAccessedBy()
    {
        return $this->belongsTo(User::class, 'last_accessed_by');
    }

    public function recordVaultAccess(): void
    {
        $this->forceFill([
            'last_accessed_at' => now(),
            'last_accessed_by' => auth()->id(),
        ])->save();
    }

    public function logVaultAction(string $action): void
    {
        if (! in_array($action, [
            self::AUDIT_REVEALED_PASSWORD,
            self::AUDIT_REVEALED_USERNAME,
            self::AUDIT_COPIED_PASSWORD,
            self::AUDIT_COPIED_USERNAME,
        ], true)) {
            return;
        }

        $descriptions = [
            self::AUDIT_REVEALED_PASSWORD => 'revealed password for credential vault entry',
            self::AUDIT_REVEALED_USERNAME => 'revealed username for credential vault entry',
            self::AUDIT_COPIED_PASSWORD => 'copied password for credential vault entry',
            self::AUDIT_COPIED_USERNAME => 'copied username for credential vault entry',
        ];

        $this->recordVaultAccess();

        activity('credential_vault')
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->event($action)
            ->withProperties([
                'portal_name' => $this->portal_name,
                'category' => $this->category,
                'client_id' => $this->client_id,
                'client_name' => $this->client?->name,
                'field' => str_contains($action, 'password') ? 'password' : 'username',
            ])
            ->log($descriptions[$action]);
    }
}

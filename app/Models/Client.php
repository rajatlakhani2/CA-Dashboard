<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;

class Client extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;
    use \Spatie\Activitylog\Traits\LogsActivity;

    public const STATUS_ACTIVE = 'Active';
    public const STATUS_ON_HOLD = 'On-Hold';
    public const STATUS_CLOSED = 'Closed';

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'client_code', 'status', 'category', 'pan'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'client_code',
        'group_name',
        'name',
        'entity_type',
        'industry',
        'pan',
        'gstin',
        'cin',
        'tan',
        'registered_address',
        'billing_address',
        'is_same_address',
        'primary_contact_name',
        'primary_contact_phone',
        'primary_contact_email',
        'category',
        'onboarding_date',
        'manager_id',
        'created_by_user_id',
        'approval_status',
        'approved_at',
        'approved_by_user_id',
        'billing_cycle',
        'payment_terms_days',
        'gst_applicable',
        'currency',
        'invoice_email',
        'status',
        'tags',
        'branch_id',
        'office_notes',
    ];

    protected $casts = [
        'is_same_address' => 'boolean',
        'gst_applicable' => 'boolean',
        'tags' => 'array',
        'onboarding_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * WhatsApp and reminders use this alias; stored as primary_contact_phone.
     */
    public function getMobileNumberAttribute(): ?string
    {
        return $this->primary_contact_phone;
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isArticle()) {
            return $query->whereRaw('0 = 1');
        }

        if ($user->isPartner()) {
            return $query;
        }

        $query = $query->where('approval_status', self::APPROVAL_APPROVED);

        if ($user->isManager()) {
            if (! $user->branch_id) {
                return $query;
            }

            return $query->where(function (Builder $branchQuery) use ($user) {
                $branchQuery->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        if ($user->isAssociate()) {
            return $query->where('manager_id', $user->id);
        }

        return $query->where(function (Builder $assignmentQuery) use ($user) {
            $assignmentQuery->where('manager_id', $user->id)
                ->orWhereHas('tasks', function (Builder $taskQuery) use ($user) {
                    $taskQuery->where(function (Builder $ownershipQuery) use ($user) {
                        $ownershipQuery->where('assigned_to', $user->id)
                            ->orWhere('created_by', $user->id);
                    });
                });
        });
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ClientService::class);
    }

    public function optedServices(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'client_services')->withPivot('id', 'status', 'custom_due_day');
    }

    public function clientServices(): HasMany
    {
        return $this->hasMany(ClientService::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ClientDocument::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function personalRenewals(): HasMany
    {
        return $this->hasMany(PersonalRenewal::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function worksheets(): HasMany
    {
        return $this->hasMany(ClientWorksheet::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(ClientCredential::class);
    }
}

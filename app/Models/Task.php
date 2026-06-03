<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Task extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_ON_HOLD = 'On Hold';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_DONE = 'Done';
    public const STATUS_CLOSED = 'Closed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ON_HOLD,
        self::STATUS_COMPLETED,
    ];

    public const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_DONE,
        self::STATUS_CLOSED,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'assigned_to'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'client_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'due_date',
        'priority',
        'is_billed',
        'invoice_id',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_billed' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function totalHours(): float
    {
        return (float) $this->timeEntries()->sum('hours');
    }

    /**
     * Completed work that still needs invoicing or FOC.
     * Partners/managers see all firm tasks (including unassigned).
     * Others see tasks assigned to them or unassigned tasks they created.
     */
    public function scopeUnbilledForUser(Builder $query, User $user): Builder
    {
        $query->whereIn('status', self::TERMINAL_STATUSES)
            ->where(function (Builder $q) {
                $q->where('is_billed', false)
                    ->orWhere('is_billed', 0)
                    ->orWhereNull('is_billed');
            });

        // Partner/manager: every completed unbilled task (including unassigned).
        if ($user->isPartner() || $user->isManager()) {
            return $query;
        }

        // Others: assigned to them, or unassigned tasks they created.
        return $query->where(function (Builder $q) use ($user) {
            $q->where('assigned_to', $user->id)
                ->orWhere(function (Builder $inner) use ($user) {
                    $inner->whereNull('assigned_to')
                        ->where('created_by', $user->id);
                });
        });
    }
}

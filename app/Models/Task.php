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
    use \App\Models\Concerns\BelongsToOrganization;

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
        'priority_auto',
        'is_billed',
        'invoice_id',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_billed' => 'boolean',
        'priority_auto' => 'boolean',
    ];

    /*
     * Priority (firm rule, Nov 2026)
     * - "Auto" (the default): set from the due date and updated every day as the date comes closer:
     *   due within 3 days (or overdue) = High, within 6 days = Medium, later = Low. No due date = Low.
     * - Or the person picks High / Medium / Low themselves; that choice is kept as it is.
     */
    public const PRIORITY_AUTO = 'Auto';

    public const PRIORITY_HIGH = 'High';

    public const PRIORITY_MEDIUM = 'Medium';

    public const PRIORITY_LOW = 'Low';

    public const PRIORITIES = [self::PRIORITY_HIGH, self::PRIORITY_MEDIUM, self::PRIORITY_LOW];

    public const HIGH_WITHIN_DAYS = 3;

    public const MEDIUM_WITHIN_DAYS = 6;

    public static function priorityForDueDate($dueDate): string
    {
        if (! $dueDate) {
            return self::PRIORITY_LOW;
        }
        $days = (int) now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($dueDate)->startOfDay(), false);

        return match (true) {
            $days <= self::HIGH_WITHIN_DAYS => self::PRIORITY_HIGH,
            $days <= self::MEDIUM_WITHIN_DAYS => self::PRIORITY_MEDIUM,
            default => self::PRIORITY_LOW,
        };
    }

    protected static function booted(): void
    {
        static::saving(function (Task $task) {
            $chosen = $task->priority;
            if ($chosen === self::PRIORITY_AUTO || $chosen === null || $chosen === '' || $chosen === 'Normal') {
                $task->priority_auto = true;
            } elseif ($task->isDirty('priority') && ! $task->isDirty('priority_auto')) {
                $task->priority_auto = false; // someone picked High / Medium / Low
            }

            if ($task->priority_auto ?? true) {
                $task->priority_auto = true;
                if (! in_array($task->status, self::TERMINAL_STATUSES, true) || ! in_array($task->priority, self::PRIORITIES, true)) {
                    $task->priority = self::priorityForDueDate($task->due_date);
                }
            }
        });
    }

    /** Move "Auto" tasks to the right priority as their due dates come closer (all firms). */
    public static function refreshAutoPriorities(): int
    {
        $today = now()->startOfDay();
        $base = fn () => static::withoutGlobalScopes()->where('priority_auto', true)
            ->whereNotIn('status', self::TERMINAL_STATUSES)->toBase();
        $changed = 0;
        $changed += $base()->whereNotNull('due_date')->whereDate('due_date', '<=', $today->copy()->addDays(self::HIGH_WITHIN_DAYS))
            ->where('priority', '!=', self::PRIORITY_HIGH)->update(['priority' => self::PRIORITY_HIGH]);
        $changed += $base()->whereDate('due_date', '>', $today->copy()->addDays(self::HIGH_WITHIN_DAYS))
            ->whereDate('due_date', '<=', $today->copy()->addDays(self::MEDIUM_WITHIN_DAYS))
            ->where('priority', '!=', self::PRIORITY_MEDIUM)->update(['priority' => self::PRIORITY_MEDIUM]);
        $changed += $base()->where(fn ($q) => $q->whereNull('due_date')->orWhereDate('due_date', '>', $today->copy()->addDays(self::MEDIUM_WITHIN_DAYS)))
            ->where('priority', '!=', self::PRIORITY_LOW)->update(['priority' => self::PRIORITY_LOW]);

        return $changed;
    }

    /** Runs the daily refresh at most once a day, even if the server's scheduler is not running. */
    public static function refreshAutoPrioritiesOncePerDay(): void
    {
        try {
            if (\Illuminate\Support\Facades\Cache::add('tasks:priority-refreshed:'.now()->toDateString(), 1, now()->endOfDay())) {
                static::refreshAutoPriorities();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** What the priority picker should show for this task: "Auto" or the chosen level. */
    public function priorityChoice(): string
    {
        return $this->priority_auto ? self::PRIORITY_AUTO : (in_array($this->priority, self::PRIORITIES, true) ? $this->priority : self::PRIORITY_AUTO);
    }

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

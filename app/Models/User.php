<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /** Demo logins from FirmTeamSeeder — not shown on workload until real staff are added. */
    public const SEED_PLACEHOLDER_EMAILS = [
        'associate@rlassociates.in',
        'article@rlassociates.in',
        'associate@rla.local',
        'article@rla.local',
    ];

    public const SEED_PLACEHOLDER_NAMES = [
        'articles',
        'firm associate',
        'article clerk',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'role',
        'theme',
        'timezone',
        'branch_id',
        'module_access',
        'organization_id',
        'demo_tour_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'module_access' => 'array',
            'demo_tour_completed_at' => 'datetime',
        ];
    }

    public function resolvedModuleAccess(): array
    {
        $stored = is_array($this->module_access) ? $this->module_access : [];

        return array_merge(
            \App\Support\ModuleAccess::defaultsForRole((string) $this->role),
            $stored
        );
    }

    public function canAccessModule(string $module): bool
    {
        return \App\Support\ModuleGate::allowed($this, $module);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function managedClients()
    {
        return $this->hasMany(Client::class, 'manager_id');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeInOrganization($query, ?int $organizationId)
    {
        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query;
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    // RBAC Helpers
    public function isPartner(): bool
    {
        return strtolower((string) $this->role) === 'partner';
    }

    public function isCeo(): bool
    {
        return strtolower((string) $this->role) === 'ceo';
    }

    /** Partner (CA firm) or CEO (executive workspace) — full workspace administration. */
    public function isWorkspaceOwner(): bool
    {
        return $this->isPartner() || $this->isCeo();
    }
    public function isManager(): bool
    {
        return strtolower((string) $this->role) === 'manager';
    }
    public function isStaff(): bool
    {
        return strtolower((string) $this->role) === 'staff';
    }
    public function isIntern(): bool
    {
        return strtolower((string) $this->role) === 'intern';
    }

    public function isAssociate(): bool
    {
        return strtolower((string) $this->role) === 'associate';
    }

    public function isArticle(): bool
    {
        return strtolower((string) $this->role) === 'article';
    }

    /** Staff roles that land on My Day after login (not partner dashboard). */
    public function prefersMyDayHome(): bool
    {
        return $this->isArticle() || $this->isStaff() || $this->isIntern();
    }

    public function managesFirmModules(): bool
    {
        return $this->hasRole('partner', 'manager', 'ceo');
    }

    public function canViewPortfolioInvoices(): bool
    {
        if (! $this->canAccessModule('invoices')) {
            return false;
        }

        return $this->managesFirmModules() || $this->isAssociate();
    }

    public function hasRole(string ...$roles): bool
    {
        $normalizedRoles = array_map('strtolower', $roles);

        return in_array(strtolower((string) $this->role), $normalizedRoles, true);
    }

    /** Hide legacy demo accounts from team/workload UIs. */
    public function scopeVisibleInTeam(Builder $query): Builder
    {
        return $query
            ->whereNotIn('email', ['nilesh@rlassociates.in', 'nilesh@rla.local'])
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%nilesh%'])
            ->whereRaw('LOWER(name) != ?', ['article clerk'])
            ->where(function (Builder $q) {
                $q->where('role', '!=', 'article')
                    ->orWhereRaw('LOWER(email) = ?', ['article@rlassociates.in']);
            })
            ->where(function (Builder $q) {
                $q->where('role', '!=', 'associate')
                    ->orWhereRaw('LOWER(name) != ?', ['firm associate'])
                    ->orWhereRaw('LOWER(email) = ?', ['associate@rlassociates.in']);
            });
    }

    public function isSeedPlaceholder(): bool
    {
        $email = strtolower(trim((string) $this->email));
        if (in_array($email, self::SEED_PLACEHOLDER_EMAILS, true)) {
            return true;
        }

        return in_array(strtolower(trim((string) $this->name)), self::SEED_PLACEHOLDER_NAMES, true);
    }

    /** Staff columns on workload — real team members only, not seeded demo logins. */
    public function scopeForWorkload(Builder $query): Builder
    {
        return $query
            ->whereNotIn('email', ['nilesh@rlassociates.in', 'nilesh@rla.local'])
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%nilesh%'])
            ->where(function (Builder $q) {
                foreach (self::SEED_PLACEHOLDER_EMAILS as $email) {
                    $q->whereRaw('LOWER(email) != ?', [$email]);
                }
            })
            ->where(function (Builder $q) {
                foreach (self::SEED_PLACEHOLDER_NAMES as $name) {
                    $q->whereRaw('LOWER(name) != ?', [$name]);
                }
            });
    }
}

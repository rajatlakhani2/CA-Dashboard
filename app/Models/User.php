<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'theme',
        'branch_id',
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
        ];
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

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    // RBAC Helpers
    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }
    public function isIntern(): bool
    {
        return $this->role === 'intern';
    }
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles);
    }
}

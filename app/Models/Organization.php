<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    public const PLAN_STARTER = 'starter';

    public const PLAN_PROFESSIONAL = 'professional';

    public const PLAN_ENTERPRISE = 'enterprise';

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'seat_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'seat_limit' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasSeatAvailable(): bool
    {
        return $this->users()->count() < $this->seat_limit;
    }

    public function planLabel(): string
    {
        return match ($this->plan) {
            self::PLAN_STARTER => 'Starter',
            self::PLAN_ENTERPRISE => 'Enterprise',
            default => 'Professional',
        };
    }

    public function seatsRemaining(): int
    {
        return max(0, $this->seat_limit - $this->users()->count());
    }
}

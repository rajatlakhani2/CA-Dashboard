<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;

class Client extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;
    use \Spatie\Activitylog\Traits\LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'client_code', 'status', 'category', 'pan'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'client_code',
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
        'billing_cycle',
        'payment_terms_days',
        'gst_applicable',
        'currency',
        'invoice_email',
        'status',
        'tags'
    ];

    protected $casts = [
        'is_same_address' => 'boolean',
        'gst_applicable' => 'boolean',
        'tags' => 'array',
        'onboarding_date' => 'date'
    ];

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
        return $this->belongsToMany(Service::class, 'client_services')->withPivot('status', 'custom_due_day');
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

    public function personalRenewals(): HasMany
    {
        return $this->hasMany(PersonalRenewal::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    use \App\Models\Concerns\BelongsToOrganization;

    protected $fillable = [
        'name',
        'code',
        'description',
        'frequency',
        'due_day',
        'due_month',
        'is_statutory',
    ];

    public function clientServices()
    {
        return $this->hasMany(ClientService::class);
    }

    public function taskTemplates()
    {
        return $this->hasMany(TaskTemplate::class);
    }

    public function billingRules()
    {
        return $this->hasMany(BillingRule::class);
    }

    public function documentRequirements()
    {
        return $this->hasMany(ServiceDocumentRequirement::class)->orderBy('sort_order')->orderBy('name');
    }
}

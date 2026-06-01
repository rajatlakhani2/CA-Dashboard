<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceDocumentRequirement extends Model
{
    protected $fillable = [
        'service_id',
        'name',
        'sort_order',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(ClientServiceDocumentCheck::class);
    }
}

<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::creating(function ($model) {
            if (empty($model->organization_id) && OrganizationContext::id()) {
                $model->organization_id = OrganizationContext::id();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

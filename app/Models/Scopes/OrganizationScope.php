<?php

namespace App\Models\Scopes;

use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $orgId = OrganizationContext::id();
        if ($orgId) {
            $builder->where($model->getTable() . '.organization_id', $orgId);
        }
    }
}

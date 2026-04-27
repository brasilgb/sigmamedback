<?php

namespace App\Scopes;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = TenantContext::current();

        if (! $tenant) {
            return;
        }

        $builder->where($model->getTable().'.tenant_id', $tenant->id);
    }
}

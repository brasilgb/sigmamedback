<?php

namespace App\Models\Concerns;

use App\Scopes\TenantScope;
use App\Support\Tenancy\TenantContext;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (empty($model->tenant_id) && TenantContext::current()) {
                $model->tenant_id = TenantContext::current()->id;
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function scopeForTenant($query, $tenant = null)
    {
        $tenant ??= TenantContext::current();

        if ($tenant) {
            return $query->where('tenant_id', $tenant->id);
        }

        return $query;
    }
}

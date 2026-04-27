<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): ResponseAlias
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $tenantId = $request->header('X-Tenant-Id');
        $tenant = null;

        if ($tenantId) {
            $tenant = $user->tenants()->where('tenants.id', $tenantId)->first();
        }

        if (! $tenant) {
            $tenant = $user->tenants()->first();
        }

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant not found or access denied.',
            ], 403);
        }

        TenantContext::set($tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}

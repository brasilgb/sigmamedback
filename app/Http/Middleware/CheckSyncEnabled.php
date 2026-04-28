<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSyncEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::current();

        if ($tenant && ! $tenant->sync_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Synchronization is not enabled for this account.',
            ], 403);
        }

        return $next($request);
    }
}

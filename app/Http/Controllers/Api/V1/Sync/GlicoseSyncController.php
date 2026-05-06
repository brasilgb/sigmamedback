<?php

namespace App\Http\Controllers\Api\V1\Sync;

use App\Http\Controllers\Api\V1\Sync\Concerns\ValidatesSyncOwnership;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncGlicoseRequest;
use App\Models\GlicoseReading;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class GlicoseSyncController extends Controller
{
    use ValidatesSyncOwnership;

    public function sync(SyncGlicoseRequest $request): JsonResponse
    {
        $tenant = TenantContext::current();
        $userId = $request->user()->id;
        $items = collect($request->validated('items'));

        $results = $items->map(function (array $item) use ($tenant, $userId) {
            $this->ensureProfileBelongsToAuthenticatedUser($tenant->id, $userId, (int) $item['profile_id']);

            $reading = GlicoseReading::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            if (! $reading) {
                $reading = new GlicoseReading([
                    'uuid' => $item['uuid'],
                    'tenant_id' => $tenant->id,
                    'profile_id' => $item['profile_id'],
                ]);
            }

            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;

            $reading->fill([
                'glicose_value' => $item['glicose_value'],
                'unit' => $item['unit'],
                'context' => $item['context'] ?? null,
                'measured_at' => $item['measured_at'],
                'source' => $item['source'],
                'notes' => $item['notes'] ?? null,
            ]);

            if ($incomingDeletedAt) {
                $reading->deleted_at = $incomingDeletedAt;
            } elseif ($reading->deleted_at && Carbon::parse($item['measured_at'])->gt($reading->deleted_at)) {
                $reading->deleted_at = null;
            }

            $reading->save();

            return $reading;
        });

        return $this->successResponse($results, 'Sincronização de glicose concluída.');
    }
}

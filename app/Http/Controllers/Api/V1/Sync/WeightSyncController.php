<?php

namespace App\Http\Controllers\Api\V1\Sync;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncWeightRequest;
use App\Models\WeightReading;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class WeightSyncController extends Controller
{
    public function sync(SyncWeightRequest $request): JsonResponse
    {
        $tenant = TenantContext::current();
        $items = collect($request->validated('items'));

        $results = $items->map(function (array $item) use ($tenant) {
            $reading = WeightReading::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            if (! $reading) {
                $reading = new WeightReading([
                    'uuid' => $item['uuid'],
                    'tenant_id' => $tenant->id,
                    'profile_id' => $item['profile_id'],
                ]);
            }

            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;

            $reading->fill([
                'weight' => $item['weight'],
                'height' => $item['height'] ?? null,
                'unit' => $item['unit'],
                'measured_at' => $item['measured_at'],
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

        return $this->successResponse($results, 'Sincronização de peso concluída.');
    }
}

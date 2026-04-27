<?php

namespace App\Http\Controllers\Api\V1\Sync;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncBloodPressureRequest;
use App\Models\BloodPressureReading;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class BloodPressureSyncController extends Controller
{
    public function sync(SyncBloodPressureRequest $request): JsonResponse
    {
        $tenant = TenantContext::current();
        $items = collect($request->validated('items'));

        $results = $items->map(function (array $item) use ($tenant) {
            $reading = BloodPressureReading::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            if (! $reading) {
                $reading = new BloodPressureReading([
                    'uuid' => $item['uuid'],
                    'tenant_id' => $tenant->id,
                    'profile_id' => $item['profile_id'],
                ]);
            }

            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;

            $reading->fill([
                'systolic' => $item['systolic'],
                'diastolic' => $item['diastolic'],
                'pulse' => $item['pulse'],
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

        return $this->successResponse($results, 'Blood pressure sync completed.');
    }
}

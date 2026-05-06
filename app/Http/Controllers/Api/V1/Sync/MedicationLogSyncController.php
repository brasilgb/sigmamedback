<?php

namespace App\Http\Controllers\Api\V1\Sync;

use App\Http\Controllers\Api\V1\Sync\Concerns\ValidatesSyncOwnership;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncMedicationLogsRequest;
use App\Models\MedicationLog;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class MedicationLogSyncController extends Controller
{
    use ValidatesSyncOwnership;

    public function sync(SyncMedicationLogsRequest $request): JsonResponse
    {
        $tenant = TenantContext::current();
        $userId = $request->user()->id;
        $items = collect($request->validated('items'));

        $results = $items->map(function (array $item) use ($tenant, $userId) {
            $profileId = (int) $item['profile_id'];
            $this->ensureProfileBelongsToAuthenticatedUser($tenant->id, $userId, $profileId);

            $log = MedicationLog::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;
            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;
            $medicationId = $this->resolveMedicationIdForLog(
                tenantId: $tenant->id,
                profileId: $profileId,
                medicationId: $item['medication_id'] ?? null,
                medicationUuid: $item['medication_uuid'] ?? null,
            );

            if (! $log) {
                $log = new MedicationLog([
                    'uuid' => $item['uuid'],
                    'tenant_id' => $tenant->id,
                    'profile_id' => $profileId,
                    'medication_id' => $medicationId,
                ]);
            }

            $log->fill([
                'taken_at' => $item['taken_at'],
                'notes' => $item['notes'] ?? null,
            ]);

            if ($incomingDeletedAt) {
                $log->deleted_at = $incomingDeletedAt;
            } elseif ($log->deleted_at && $incomingUpdatedAt && $incomingUpdatedAt->gt($log->deleted_at)) {
                $log->deleted_at = null;
            }

            $log->save();

            return $log;
        });

        return $this->successResponse($results, 'Sincronização de registros de medicação concluída.');
    }
}

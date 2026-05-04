<?php

namespace App\Http\Controllers\Api\V1\Sync;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncMedicationLogsRequest;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class MedicationLogSyncController extends Controller
{
    public function sync(SyncMedicationLogsRequest $request): JsonResponse
    {
        $tenant = TenantContext::current();
        $items = collect($request->validated('items'));

        $results = $items->map(function (array $item) use ($tenant) {
            $log = MedicationLog::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;
            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;
            $medicationId = $item['medication_id'] ?? null;

            if (! $medicationId && isset($item['medication_uuid'])) {
                $medicationId = Medication::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('uuid', $item['medication_uuid'])
                    ->value('id');
            }

            if (! $log) {
                $log = new MedicationLog([
                    'uuid' => $item['uuid'],
                    'tenant_id' => $tenant->id,
                    'profile_id' => $item['profile_id'],
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

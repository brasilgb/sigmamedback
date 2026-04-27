<?php

namespace App\Http\Controllers\Api\V1\Sync;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncMedicationRequest;
use App\Models\Medication;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class MedicationSyncController extends Controller
{
    public function sync(SyncMedicationRequest $request): JsonResponse
    {
        $tenant = TenantContext::current();
        $items = collect($request->validated('items'));

        $results = $items->map(function (array $item) use ($tenant) {
            $medication = Medication::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            if (! $medication) {
                $medication = new Medication([
                    'uuid' => $item['uuid'],
                    'tenant_id' => $tenant->id,
                    'profile_id' => $item['profile_id'],
                ]);
            }

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;
            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;

            $medication->fill([
                'name' => $item['name'],
                'dosage' => $item['dosage'] ?? null,
                'instructions' => $item['instructions'] ?? null,
                'active' => $item['active'],
                'scheduled_time' => $item['scheduled_time'] ?? null,
                'reminder_enabled' => $item['reminder_enabled'],
                'repeat_reminder_every_five_minutes' => $item['repeat_reminder_every_five_minutes'],
                'reminder_minutes_before' => $item['reminder_minutes_before'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);

            if ($incomingDeletedAt) {
                $medication->deleted_at = $incomingDeletedAt;
            } elseif ($medication->deleted_at && $incomingUpdatedAt && $incomingUpdatedAt->gt($medication->deleted_at)) {
                $medication->deleted_at = null;
            }

            $medication->save();

            return $medication;
        });

        return $this->successResponse($results, 'Medication sync completed.');
    }
}

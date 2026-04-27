<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncPullRequest;
use App\Http\Requests\Api\V1\SyncPushRequest;
use App\Models\BloodPressureReading;
use App\Models\GlicoseReading;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\WeightReading;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class SyncController extends Controller
{
    public function push(SyncPushRequest $request): JsonResponse
    {
        $tenant = TenantContext::current();
        $resource = $request->resource;
        $items = collect($request->validated('items'));

        $results = match ($resource) {
            'blood-pressure' => $this->syncBloodPressure($tenant, $items),
            'glicose' => $this->syncGlicose($tenant, $items),
            'weight' => $this->syncWeight($tenant, $items),
            'medications' => $this->syncMedications($tenant, $items),
            'medication-logs' => $this->syncMedicationLogs($tenant, $items),
        };

        return $this->successResponse($results, ucfirst($resource) . ' push completed.');
    }

    public function pull(SyncPullRequest $request): JsonResponse
    {
        $tenant = TenantContext::current();
        $resource = $request->resource;
        $since = $request->input('since');

        $items = match ($resource) {
            'blood-pressure' => $this->pullResource(BloodPressureReading::class, $tenant->id, $since),
            'glicose' => $this->pullResource(GlicoseReading::class, $tenant->id, $since),
            'weight' => $this->pullResource(WeightReading::class, $tenant->id, $since),
            'medications' => $this->pullResource(Medication::class, $tenant->id, $since),
            'medication-logs' => $this->pullResource(MedicationLog::class, $tenant->id, $since),
        };

        return $this->successResponse($items, ucfirst($resource) . ' pull completed.');
    }

    protected function syncBloodPressure($tenant, $items)
    {
        return $items->map(function (array $item) use ($tenant) {
            $reading = BloodPressureReading::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;

            if ($reading && $incomingUpdatedAt && $reading->updated_at->gt($incomingUpdatedAt)) {
                return $reading;
            }

            if (! $reading) {
                $reading = new BloodPressureReading([
                    'uuid' => $item['uuid'],
                    'tenant_id' => $tenant->id,
                    'profile_id' => $item['profile_id'],
                ]);
            }

            $reading->fill([
                'systolic' => $item['systolic'] ?? 0,
                'diastolic' => $item['diastolic'] ?? 0,
                'pulse' => $item['pulse'] ?? 0,
                'measured_at' => $item['measured_at'] ?? now(),
                'source' => $item['source'] ?? 'manual',
                'notes' => $item['notes'] ?? null,
            ]);

            if ($incomingUpdatedAt) {
                $reading->updated_at = $incomingUpdatedAt;
            }

            $reading->save();

            return $reading;
        });
    }

    protected function syncGlicose($tenant, $items)
    {
        return $items->map(function (array $item) use ($tenant) {
            $reading = GlicoseReading::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;

            if ($reading && $incomingUpdatedAt && $reading->updated_at->gt($incomingUpdatedAt)) {
                return $reading;
            }

            if (! $reading) {
                $reading = new GlicoseReading([
                    'uuid' => $item['uuid'],
                    'tenant_id' => $tenant->id,
                    'profile_id' => $item['profile_id'],
                ]);
            }

            $reading->fill([
                'glicose_value' => $item['glicose_value'] ?? 0,
                'unit' => $item['unit'] ?? 'mg/dL',
                'context' => $item['context'] ?? null,
                'measured_at' => $item['measured_at'] ?? now(),
                'source' => $item['source'] ?? 'manual',
                'notes' => $item['notes'] ?? null,
            ]);

            if ($incomingUpdatedAt) {
                $reading->updated_at = $incomingUpdatedAt;
            }

            $reading->save();

            return $reading;
        });
    }

    protected function syncWeight($tenant, $items)
    {
        return $items->map(function (array $item) use ($tenant) {
            $reading = WeightReading::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;

            if ($reading && $incomingUpdatedAt && $reading->updated_at->gt($incomingUpdatedAt)) {
                return $reading;
            }

            if (! $reading) {
                $reading = new WeightReading([
                    'uuid' => $item['uuid'],
                    'tenant_id' => $tenant->id,
                    'profile_id' => $item['profile_id'],
                ]);
            }

            $reading->fill([
                'weight' => $item['weight'] ?? 0,
                'height' => $item['height'] ?? null,
                'unit' => $item['unit'] ?? 'kg',
                'measured_at' => $item['measured_at'] ?? now(),
                'notes' => $item['notes'] ?? null,
            ]);

            if ($incomingUpdatedAt) {
                $reading->updated_at = $incomingUpdatedAt;
            }

            $reading->save();

            return $reading;
        });
    }

    protected function syncMedications($tenant, $items)
    {
        return $items->map(function (array $item) use ($tenant) {
            $medication = Medication::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;

            if ($medication && $incomingUpdatedAt && $medication->updated_at->gt($incomingUpdatedAt)) {
                return $medication;
            }

            if (! $medication) {
                $medication = new Medication([
                    'uuid' => $item['uuid'],
                    'tenant_id' => $tenant->id,
                    'profile_id' => $item['profile_id'],
                ]);
            }

            $medication->fill([
                'name' => $item['name'] ?? '',
                'dosage' => $item['dosage'] ?? null,
                'instructions' => $item['instructions'] ?? null,
                'active' => $item['active'] ?? false,
                'scheduled_time' => $item['scheduled_time'] ?? null,
                'reminder_enabled' => $item['reminder_enabled'] ?? false,
                'repeat_reminder_every_five_minutes' => $item['repeat_reminder_every_five_minutes'] ?? false,
                'reminder_minutes_before' => $item['reminder_minutes_before'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);

            if ($incomingUpdatedAt) {
                $medication->updated_at = $incomingUpdatedAt;
            }

            $medication->save();

            return $medication;
        });
    }

    protected function syncMedicationLogs($tenant, $items)
    {
        return $items->map(function (array $item) use ($tenant) {
            $log = MedicationLog::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;
            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;

            if ($log && $incomingUpdatedAt && $log->updated_at->gt($incomingUpdatedAt)) {
                return $log;
            }

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
                'taken_at' => $item['taken_at'] ?? now(),
                'notes' => $item['notes'] ?? null,
            ]);

            if ($incomingUpdatedAt) {
                $log->updated_at = $incomingUpdatedAt;
            }

            if ($incomingDeletedAt) {
                $log->deleted_at = $incomingDeletedAt;
            } elseif ($log->deleted_at && $incomingUpdatedAt && $incomingUpdatedAt->gt($log->deleted_at)) {
                $log->deleted_at = null;
            }

            $log->save();

            return $log;
        });
    }

    protected function pullResource(string $model, int $tenantId, ?string $since)
    {
        $query = $model::withTrashed()->where('tenant_id', $tenantId);

        if ($since) {
            $query->where(function ($query) use ($since) {
                $query->where('updated_at', '>', $since)
                    ->orWhere('deleted_at', '>', $since);
            });
        }

        return $query->get();
    }
}

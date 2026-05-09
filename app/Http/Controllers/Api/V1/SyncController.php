<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Sync\Concerns\ValidatesSyncOwnership;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncPullRequest;
use App\Http\Requests\Api\V1\SyncPushRequest;
use App\Models\BloodPressureReading;
use App\Models\GlicoseReading;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\Profile;
use App\Models\WeightReading;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class SyncController extends Controller
{
    use ValidatesSyncOwnership;

    public function push(SyncPushRequest $request): JsonResponse
    {
        $tenant = TenantContext::current();
        $userId = $request->user()->id;
        $resource = $request->resource;
        $items = collect($request->validated('items'));

        $results = match ($resource) {
            'blood-pressure' => $this->syncBloodPressure($tenant, $userId, $items),
            'glicose' => $this->syncGlicose($tenant, $userId, $items),
            'weight' => $this->syncWeight($tenant, $userId, $items),
            'medications' => $this->syncMedications($tenant, $userId, $items),
            'medication-logs' => $this->syncMedicationLogs($tenant, $userId, $items),
        };

        return $this->syncResponse($results, $this->syncMessage($resource, 'push'));
    }

    public function pull(SyncPullRequest $request): JsonResponse
    {
        $tenant = TenantContext::current();
        $userId = $request->user()->id;
        $resource = $request->resource;
        $since = $request->input('since');

        $items = match ($resource) {
            'blood-pressure' => $this->pullResource(BloodPressureReading::class, $tenant->id, $userId, $since),
            'glicose' => $this->pullResource(GlicoseReading::class, $tenant->id, $userId, $since),
            'weight' => $this->pullResource(WeightReading::class, $tenant->id, $userId, $since),
            'medications' => $this->pullResource(Medication::class, $tenant->id, $userId, $since),
            'medication-logs' => $this->pullResource(MedicationLog::class, $tenant->id, $userId, $since),
        };

        return $this->syncResponse($items, $this->syncMessage($resource, 'pull'));
    }

    protected function syncBloodPressure($tenant, int $userId, $items)
    {
        return $items->map(function (array $item) use ($tenant, $userId) {
            $this->ensureProfileBelongsToAuthenticatedUser($tenant->id, $userId, (int) $item['profile_id']);

            $reading = BloodPressureReading::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;
            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;

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

            if ($incomingDeletedAt) {
                $reading->deleted_at = $incomingDeletedAt;
            } elseif ($reading->deleted_at && $incomingUpdatedAt && $incomingUpdatedAt->gt($reading->deleted_at)) {
                $reading->deleted_at = null;
            }

            $reading->save();

            return $reading;
        });
    }

    protected function syncGlicose($tenant, int $userId, $items)
    {
        return $items->map(function (array $item) use ($tenant, $userId) {
            $this->ensureProfileBelongsToAuthenticatedUser($tenant->id, $userId, (int) $item['profile_id']);

            $reading = GlicoseReading::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;
            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;

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

            if ($incomingDeletedAt) {
                $reading->deleted_at = $incomingDeletedAt;
            } elseif ($reading->deleted_at && $incomingUpdatedAt && $incomingUpdatedAt->gt($reading->deleted_at)) {
                $reading->deleted_at = null;
            }

            $reading->save();

            return $reading;
        });
    }

    protected function syncWeight($tenant, int $userId, $items)
    {
        return $items->map(function (array $item) use ($tenant, $userId) {
            $this->ensureProfileBelongsToAuthenticatedUser($tenant->id, $userId, (int) $item['profile_id']);

            $reading = WeightReading::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;
            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;

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

            if ($incomingDeletedAt) {
                $reading->deleted_at = $incomingDeletedAt;
            } elseif ($reading->deleted_at && $incomingUpdatedAt && $incomingUpdatedAt->gt($reading->deleted_at)) {
                $reading->deleted_at = null;
            }

            $reading->save();

            return $reading;
        });
    }

    protected function syncMedications($tenant, int $userId, $items)
    {
        return $items->map(function (array $item) use ($tenant, $userId) {
            $this->ensureProfileBelongsToAuthenticatedUser($tenant->id, $userId, (int) $item['profile_id']);

            $medication = Medication::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;
            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;

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
                'dose_interval' => $item['dose_interval'] ?? null,
                'reminder_enabled' => $item['reminder_enabled'] ?? false,
                'repeat_reminder_every_five_minutes' => $item['repeat_reminder_every_five_minutes'] ?? false,
                'reminder_minutes_before' => $item['reminder_minutes_before'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);

            if ($incomingUpdatedAt) {
                $medication->updated_at = $incomingUpdatedAt;
            }

            if ($incomingDeletedAt) {
                $medication->deleted_at = $incomingDeletedAt;
            } elseif ($medication->deleted_at && $incomingUpdatedAt && $incomingUpdatedAt->gt($medication->deleted_at)) {
                $medication->deleted_at = null;
            }

            $medication->save();

            return $medication;
        });
    }

    protected function syncMedicationLogs($tenant, int $userId, $items)
    {
        return $items->map(function (array $item) use ($tenant, $userId) {
            $profileId = (int) $item['profile_id'];
            $this->ensureProfileBelongsToAuthenticatedUser($tenant->id, $userId, $profileId);

            $log = MedicationLog::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('uuid', $item['uuid'])
                ->first();

            $incomingUpdatedAt = isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null;
            $incomingDeletedAt = isset($item['deleted_at']) ? Carbon::parse($item['deleted_at']) : null;

            if ($log && $incomingUpdatedAt && $log->updated_at->gt($incomingUpdatedAt)) {
                return $log;
            }

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
                'scheduled_at' => $item['scheduled_at'] ?? null,
                'taken_at' => $item['taken_at'] ?? now(),
                'status' => $item['status'] ?? 'taken',
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

    protected function pullResource(string $model, int $tenantId, int $userId, ?string $since)
    {
        $profileIds = Profile::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->pluck('id');

        $query = $model::withTrashed()
            ->where('tenant_id', $tenantId)
            ->whereIn('profile_id', $profileIds);

        if ($since) {
            $query->where(function ($query) use ($since) {
                $query->where('updated_at', '>', $since)
                    ->orWhere('deleted_at', '>', $since);
            });
        }

        return $query->get();
    }

    protected function syncMessage(string $resource, string $direction): string
    {
        $resourceNames = [
            'blood-pressure' => 'pressão arterial',
            'glicose' => 'glicose',
            'weight' => 'peso',
            'medications' => 'medicamentos',
            'medication-logs' => 'registros de medicação',
        ];

        $actions = [
            'push' => 'Envio',
            'pull' => 'Recebimento',
        ];

        return "{$actions[$direction]} de {$resourceNames[$resource]} concluído.";
    }

    protected function syncResponse($data, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'meta' => new \stdClass,
            'data' => $data,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Sync\Concerns;

use App\Models\Medication;
use App\Models\Profile;

trait ValidatesSyncOwnership
{
    protected function ensureProfileBelongsToAuthenticatedUser(int $tenantId, int $userId, int $profileId): void
    {
        $exists = Profile::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('id', $profileId)
            ->exists();

        if (! $exists) {
            abort(422, "profile_id inválido: {$profileId}");
        }
    }

    protected function resolveMedicationIdForLog(int $tenantId, int $profileId, ?int $medicationId, ?string $medicationUuid): int
    {
        if ($medicationId) {
            $exists = Medication::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('profile_id', $profileId)
                ->where('id', $medicationId)
                ->exists();

            if (! $exists) {
                abort(422, "medication_id inválido: {$medicationId}");
            }

            return $medicationId;
        }

        if ($medicationUuid) {
            $resolvedMedicationId = Medication::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('profile_id', $profileId)
                ->where('uuid', $medicationUuid)
                ->value('id');

            if ($resolvedMedicationId) {
                return (int) $resolvedMedicationId;
            }

            abort(422, "medication_uuid inválido: {$medicationUuid}");
        }

        abort(422, 'Informe medication_id ou medication_uuid.');
    }
}

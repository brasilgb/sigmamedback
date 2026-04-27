<?php

namespace App\Http\Requests\Api\V1;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncMedicationLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = TenantContext::current()?->id;

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.uuid' => ['required', 'uuid'],
            'items.*.profile_id' => ['required', 'integer', 'exists:profiles,id'],
            'items.*.updated_at' => ['sometimes', 'nullable', 'date'],
            'items.*.medication_id' => ['sometimes', 'integer', 'exists:medications,id', 'required_without:items.*.medication_uuid'],
            'items.*.medication_uuid' => ['sometimes', 'uuid', Rule::exists('medications', 'uuid')->where('tenant_id', $tenantId), 'required_without:items.*.medication_id'],
            'items.*.taken_at' => ['required', 'date'],
            'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'items.*.deleted_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}

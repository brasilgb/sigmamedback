<?php

namespace App\Http\Requests\Api\V1;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'resource' => ['required', 'string', 'in:blood-pressure,glicose,weight,medications,medication-logs'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.uuid' => ['required', 'uuid'],
            'items.*.profile_id' => ['required', 'integer', 'exists:profiles,id'],
            'items.*.updated_at' => ['sometimes', 'nullable', 'date'],
            'items.*.deleted_at' => ['sometimes', 'nullable', 'date'],
        ];

        switch ($this->input('resource')) {
            case 'blood-pressure':
                $rules = array_merge($rules, [
                    'items.*.systolic' => ['required', 'integer', 'min:0'],
                    'items.*.diastolic' => ['required', 'integer', 'min:0'],
                    'items.*.pulse' => ['required', 'integer', 'min:0'],
                    'items.*.measured_at' => ['required', 'date'],
                    'items.*.source' => ['required', 'in:manual'],
                    'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
                ]);
                break;
            case 'glicose':
                $rules = array_merge($rules, [
                    'items.*.glicose_value' => ['required', 'numeric', 'min:0'],
                    'items.*.unit' => ['required', 'string', 'max:20'],
                    'items.*.context' => ['sometimes', 'nullable', 'string', 'max:255'],
                    'items.*.measured_at' => ['required', 'date'],
                    'items.*.source' => ['required', 'in:manual'],
                    'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
                ]);
                break;
            case 'weight':
                $rules = array_merge($rules, [
                    'items.*.weight' => ['required', 'numeric', 'min:0'],
                    'items.*.height' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                    'items.*.unit' => ['required', 'string', 'max:20'],
                    'items.*.measured_at' => ['required', 'date'],
                    'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
                ]);
                break;
            case 'medications':
                $rules = array_merge($rules, [
                    'items.*.name' => ['required', 'string', 'max:255'],
                    'items.*.dosage' => ['sometimes', 'nullable', 'string', 'max:255'],
                    'items.*.instructions' => ['sometimes', 'nullable', 'string', 'max:2000'],
                    'items.*.active' => ['required', 'boolean'],
                    'items.*.scheduled_time' => ['sometimes', 'nullable', 'date'],
                    'items.*.reminder_enabled' => ['required', 'boolean'],
                    'items.*.repeat_reminder_every_five_minutes' => ['required', 'boolean'],
                    'items.*.reminder_minutes_before' => ['sometimes', 'nullable', 'integer', 'min:0'],
                    'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
                ]);
                break;
            case 'medication-logs':
                $tenantId = TenantContext::current()?->id;

                $rules = array_merge($rules, [
                    'items.*.medication_id' => ['sometimes', 'integer', 'exists:medications,id', 'required_without:items.*.medication_uuid'],
                    'items.*.medication_uuid' => ['sometimes', 'uuid', Rule::exists('medications', 'uuid')->where('tenant_id', $tenantId), 'required_without:items.*.medication_id'],
                    'items.*.taken_at' => ['required', 'date'],
                    'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
                ]);
                break;
        }

        return $rules;
    }
}

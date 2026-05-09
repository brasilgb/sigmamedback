<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.uuid' => ['required', 'uuid'],
            'items.*.profile_id' => ['required', 'integer', 'exists:profiles,id'],
            'items.*.updated_at' => ['sometimes', 'nullable', 'date'],
            'items.*.deleted_at' => ['sometimes', 'nullable', 'date'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.dosage' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.instructions' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'items.*.active' => ['required', 'boolean'],
            'items.*.scheduled_time' => ['sometimes', 'nullable', 'date'],
            'items.*.dose_interval' => ['sometimes', 'nullable', 'date_format:H:i'],
            'items.*.reminder_enabled' => ['required', 'boolean'],
            'items.*.repeat_reminder_every_five_minutes' => ['required', 'boolean'],
            'items.*.reminder_minutes_before' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}

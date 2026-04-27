<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncBloodPressureRequest extends FormRequest
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
            'items.*.systolic' => ['required', 'integer', 'min:0'],
            'items.*.diastolic' => ['required', 'integer', 'min:0'],
            'items.*.pulse' => ['required', 'integer', 'min:0'],
            'items.*.measured_at' => ['required', 'date'],
            'items.*.source' => ['required', 'in:manual'],
            'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}

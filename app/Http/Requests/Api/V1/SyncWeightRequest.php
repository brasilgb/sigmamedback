<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncWeightRequest extends FormRequest
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
            'items.*.weight' => ['required', 'numeric', 'min:0'],
            'items.*.height' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['required', 'string', 'max:20'],
            'items.*.measured_at' => ['required', 'date'],
            'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
            'height' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:300'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}

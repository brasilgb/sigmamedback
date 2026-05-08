<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$this->user()?->id],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
            'birth_date' => ['sometimes', 'nullable', 'date'],
            'sex' => ['sometimes', 'nullable', 'string', 'max:20'],
            'photo_path' => ['sometimes', 'string', 'max:1024'],
            'height' => ['sometimes', 'numeric', 'min:0'],
            'target_weight' => ['sometimes', 'numeric', 'min:0'],
            'has_diabetes' => ['sometimes', 'boolean'],
            'has_hypertension' => ['sometimes', 'boolean'],
        ];
    }
}

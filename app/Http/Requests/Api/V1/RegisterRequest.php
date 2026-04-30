<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
            'height' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:300'],
            'account_usage' => ['required', 'string', 'in:personal,family,professional'],
            'patient_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}

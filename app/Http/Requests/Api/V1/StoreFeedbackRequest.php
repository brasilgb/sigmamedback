<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['nullable', 'required_without:comment', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'required_without:rating', 'string'],
            'source' => ['sometimes', 'nullable', 'string', 'max:100'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:50'],
            'platform' => ['sometimes', 'nullable', 'string', 'in:ios,android,web'],
        ];
    }
}

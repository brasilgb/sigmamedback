<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SyncPullRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resource' => ['required', 'string', 'in:blood-pressure,glicose,weight,medications,medication-logs'],
            'since' => ['sometimes', 'nullable', 'date'],
        ];
    }
}

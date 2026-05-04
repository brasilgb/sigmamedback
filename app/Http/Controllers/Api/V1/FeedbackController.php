<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFeedbackRequest;
use App\Models\Feedback;
use App\Support\Tenancy\TenantContext;

class FeedbackController extends Controller
{
    public function store(StoreFeedbackRequest $request)
    {
        $tenant = TenantContext::current();

        $feedback = Feedback::create([
            ...$request->validated(),
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'source' => $request->input('source') ?? 'home',
        ]);

        return $this->successResponse([
            'id' => $feedback->id,
            'rating' => $feedback->rating,
            'comment' => $feedback->comment,
            'source' => $feedback->source,
            'created_at' => $feedback->created_at,
        ], 'Feedback recebido.', status: 201);
    }
}

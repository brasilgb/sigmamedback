<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $tenant = TenantContext::current();

        $profile = $request->user()->profiles()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
        ], [
            'uuid' => Str::uuid()->toString(),
            'name' => $request->user()->name,
        ]);

        return $this->successResponse($profile, 'Profile loaded.');
    }

    public function update(UpdateProfileRequest $request)
    {
        $tenant = TenantContext::current();

        $profile = $request->user()
            ->profiles()
            ->firstOrCreate([
                'tenant_id' => $tenant->id,
                'user_id' => $request->user()->id,
            ], [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => $request->user()->name,
            ]);

        $profile->fill($request->validated());
        $profile->save();

        return response()->json([
            'data' => $profile,
            'message' => 'Profile updated.',
        ]);
    }
}

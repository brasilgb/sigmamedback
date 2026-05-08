<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProfileRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Models\Profile;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $tenant = TenantContext::current();
        $profiles = $request->user()->profiles()->where('tenant_id', $tenant->id)->get();

        return $this->successResponse($profiles, 'Perfis carregados.');
    }

    public function store(StoreProfileRequest $request)
    {
        $tenant = TenantContext::current();

        $profile = Profile::create([
            'uuid' => Str::uuid()->toString(),
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'age' => $request->input('age'),
            'birth_date' => $request->input('birth_date'),
            'sex' => $request->input('sex'),
            'height' => $request->height,
            'notes' => $request->notes,
        ]);

        return $this->successResponse($profile, 'Perfil criado.', status: 201);
    }

    public function show(Request $request)
    {
        $tenant = TenantContext::current();

        $profile = $request->user()->profiles()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
        ], [
            'uuid' => Str::uuid()->toString(),
            'name' => $request->user()->name,
            'birth_date' => null,
            'sex' => null,
        ]);

        return $this->successResponse($profile, 'Perfil carregado.');
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
                'uuid' => Str::uuid()->toString(),
                'name' => $request->user()->name,
                'birth_date' => null,
                'sex' => null,
            ]);

        $profile->fill($request->validated());
        $profile->save();

        return response()->json([
            'data' => $profile,
            'message' => 'Perfil atualizado.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Requests\Api\V1\UploadAvatarRequest;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $accountUsage = $request->input('account_usage');

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'age' => $request->input('age'),
        ]);

        $tenant = Tenant::create([
            'uuid' => Str::uuid()->toString(),
            'name' => $user->name,
            'slug' => Str::slug($user->name) ?: Str::uuid()->toString(),
            'owner_id' => $user->id,
            'account_usage' => $accountUsage,
        ]);

        $tenant->users()->attach($user, ['role' => 'owner']);

        $profile = Profile::create([
            'uuid' => Str::uuid()->toString(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'height' => $request->input('height'),
            'notes' => $accountUsage === 'personal' ? 'Perfil pessoal' : ($accountUsage === 'family' ? 'Acompanhamento familiar' : 'Acompanhamento profissional'),
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'amount' => 0,
            'status' => 'inactive',
            'payment_method' => 'none',
            'plan_type' => $accountUsage,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'tenant' => $tenant,
            'profile' => $profile,
            'token' => $token,
        ], 'Cadastro realizado com sucesso.');
    }

    public function login(LoginRequest $request)
    {
        if (! Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
            'is_admin' => false,
        ])) {
            return $this->errorResponse('Credenciais inválidas.', 422);
        }

        $user = Auth::user();
        $token = $user->createToken('mobile')->plainTextToken;
        $tenant = $user->currentTenant();
        $profile = $user->profiles()->where('tenant_id', $tenant?->id)->first();

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
            'tenant' => $tenant,
            'profile' => $profile,
        ], 'Login realizado com sucesso.');
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Logout realizado com sucesso.');
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $tenant = $user?->currentTenant();
        $profile = $user->profiles()->where('tenant_id', $tenant?->id)->first();

        return $this->successResponse([
            'user' => $user,
            'tenant' => $tenant,
            'profile' => $profile,
        ], 'Usuário autenticado.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        // Revoke all tokens
        $user->tokens()->delete();

        // Get tenants where the user is the owner
        $tenants = $user->tenants()->wherePivot('role', 'owner')->get();

        foreach ($tenants as $tenant) {
            // Delete profiles and clinical data will be handled by the database if cascade is set,
            // or we can manually delete them here.
            // Since I don't see SoftDeletes on User/Tenant, we do a force delete.
            $tenant->delete();
        }

        $user->delete();

        return $this->successResponse([], 'Conta excluída.');
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $user->fill($request->validated());
        $user->save();

        return $this->successResponse($user, 'Perfil atualizado.');
    }

    public function uploadAvatar(UploadAvatarRequest $request)
    {
        $profile = $this->resolveProfile($request);
        $path = $request->file('avatar')->store('avatars', 'public');

        $profile->photo_path = $path;
        $profile->save();

        return $this->successResponse([
            'photo_path' => $path,
            'avatar_url' => Storage::disk('public')->url($path),
        ], 'Avatar enviado.');
    }

    public function destroyAvatar(Request $request)
    {
        $profile = $this->resolveProfile($request);

        if ($profile->photo_path) {
            Storage::disk('public')->delete($profile->photo_path);
            $profile->photo_path = null;
            $profile->save();
        }

        return $this->successResponse(null, 'Avatar removido.');
    }

    protected function resolveProfile(Request $request): Profile
    {
        $tenant = TenantContext::current();

        return $request->user()->profiles()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'user_id' => $request->user()->id,
        ], [
            'uuid' => Str::uuid()->toString(),
            'name' => $request->user()->name,
        ]);
    }
}

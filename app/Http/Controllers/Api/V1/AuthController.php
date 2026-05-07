<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Requests\Api\V1\UploadAvatarRequest;
use App\Models\Profile;
use App\Services\UserAccountService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(protected UserAccountService $userAccountService) {}

    public function register(RegisterRequest $request)
    {
        ['user' => $user, 'tenant' => $tenant, 'profile' => $profile] = $this->userAccountService->register($request->validated());

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
        $this->userAccountService->deleteAccount($request->user());

        return $this->successResponse([
            'deleted' => true,
            'clear_local_data' => true,
        ], 'Conta excluída.');
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

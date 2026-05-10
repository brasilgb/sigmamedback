<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Requests\Api\V1\UploadAvatarRequest;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use App\Services\AvatarImageService;
use App\Services\UserAccountService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        protected UserAccountService $userAccountService,
        protected AvatarImageService $avatarImageService,
    ) {}

    public function register(RegisterRequest $request)
    {
        ['user' => $user, 'tenant' => $tenant, 'profile' => $profile] = $this->userAccountService->register($request->validated());

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'tenant' => $tenant,
            'profile' => $profile,
            'profile_id' => $profile->id,
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
            'profile_id' => $profile?->id,
            'account_usage' => $tenant?->account_usage ?? 'personal',
        ], 'Login realizado com sucesso.');
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $email = Str::lower($request->string('email')->toString());
        $user = User::where('email', $email)->where('is_admin', false)->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ],
            );

            $user->notify(new PasswordResetCodeNotification($code));
        }

        return $this->successResponse(null, 'Se o e-mail estiver cadastrado, enviaremos um código de recuperação.');
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $email = Str::lower($request->string('email')->toString());
        $resetToken = DB::table('password_reset_tokens')->where('email', $email)->first();
        $expiresAt = $resetToken?->created_at
            ? Carbon::parse($resetToken->created_at)->addMinutes(config('auth.passwords.users.expire'))
            : null;

        if (
            ! $resetToken
            || ! $expiresAt
            || $expiresAt->isPast()
            || ! Hash::check($request->string('code')->toString(), $resetToken->token)
        ) {
            return $this->errorResponse('Código de recuperação inválido ou expirado.', 422);
        }

        $user = User::where('email', $email)->where('is_admin', false)->first();

        if (! $user) {
            return $this->errorResponse('Código de recuperação inválido ou expirado.', 422);
        }

        $user->forceFill([
            'password' => $request->string('password')->toString(),
        ])->save();

        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return $this->successResponse(null, 'Senha redefinida com sucesso.');
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
            'profile_id' => $profile?->id,
            'account_usage' => $tenant?->account_usage ?? 'personal',
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
        $path = $this->avatarImageService->store($request->file('avatar'));

        $profile->photo_path = $path;
        $profile->save();

        return $this->successResponse([
            'photo_path' => $path,
            'avatar_url' => $profile->avatar_url,
        ], 'Avatar enviado.');
    }

    public function destroyAvatar(Request $request)
    {
        $profile = $this->resolveProfile($request);

        if ($profile->photo_path) {
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
            'birth_date' => null,
            'sex' => null,
        ]);
    }
}

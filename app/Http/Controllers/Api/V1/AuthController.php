<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Requests\Api\V1\UploadAvatarRequest;
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
        ]);

        $tenant->users()->attach($user, ['role' => 'owner']);

        $accountUsage = $request->input('account_usage');
        $profileName = $accountUsage === 'personal' ? $user->name : ($request->input('patient_name') ?? $user->name);

        $profile = Profile::create([
            'uuid' => Str::uuid()->toString(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => $profileName,
            'height' => $request->input('height'),
            'notes' => $accountUsage === 'personal' ? 'Perfil pessoal' : ($accountUsage === 'family' ? 'Acompanhamento familiar' : 'Acompanhamento profissional'),
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'tenant' => $tenant,
            'profile' => $profile,
            'token' => $token,
        ], 'Registration successful.');
    }

    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->only(['email', 'password']))) {
            return $this->errorResponse('Invalid credentials.', 422);
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
        ], 'Login successful.');
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Logged out successfully.');
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
        ], 'Authenticated user.');
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

        return $this->successResponse([], 'Account deleted.');
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        $user->fill($request->validated());
        $user->save();

        return $this->successResponse($user, 'Profile updated.');
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
        ], 'Avatar uploaded.');
    }

    public function destroyAvatar(Request $request)
    {
        $profile = $this->resolveProfile($request);

        if ($profile->photo_path) {
            Storage::disk('public')->delete($profile->photo_path);
            $profile->photo_path = null;
            $profile->save();
        }

        return $this->successResponse(null, 'Avatar removed.');
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

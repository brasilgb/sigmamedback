<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserAccountService
{
    /**
     * @param  array{name: string, email: string, password: string, age?: int|null, birth_date?: string|null, sex?: string|null, height?: float|int|null, account_usage: string}  $attributes
     * @return array{user: User, tenant: Tenant, profile: Profile}
     */
    public function register(array $attributes): array
    {
        return DB::transaction(function () use ($attributes): array {
            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => Hash::make($attributes['password']),
                'age' => $attributes['age'] ?? null,
            ]);

            $account = $this->ensureAccountForUser(
                user: $user,
                accountUsage: $attributes['account_usage'],
                age: $attributes['age'] ?? null,
                birthDate: $attributes['birth_date'] ?? null,
                sex: $attributes['sex'] ?? null,
                height: $attributes['height'] ?? null,
            );

            return [
                'user' => $user,
                ...$account,
            ];
        });
    }

    public function createOrUpdateConsoleUser(
        string $name,
        string $email,
        string $password,
        bool $isAdmin,
        string $accountUsage = 'personal',
    ): User {
        return DB::transaction(function () use ($name, $email, $password, $isAdmin, $accountUsage): User {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'is_admin' => $isAdmin,
                ],
            );

            if (! $isAdmin) {
                $this->ensureAccountForUser($user, $accountUsage);
            }

            return $user;
        });
    }

    public function deleteAccount(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();

            $user->tenants()
                ->wherePivot('role', 'owner')
                ->get()
                ->each
                ->delete();

            $user->delete();
        });
    }

    /**
     * @return array{tenant: Tenant, profile: Profile}
     */
    public function ensureAccountForUser(
        User $user,
        string $accountUsage = 'personal',
        ?int $age = null,
        ?string $birthDate = null,
        ?string $sex = null,
        float|int|null $height = null,
    ): array {
        $tenant = $user->tenants()->first();

        if (! $tenant instanceof Tenant) {
            $tenant = Tenant::create([
                'uuid' => Str::uuid()->toString(),
                'name' => $user->name,
                'slug' => $this->uniqueTenantSlug($user->name),
                'owner_id' => $user->id,
                'account_usage' => $accountUsage,
            ]);

            $tenant->users()->attach($user, ['role' => 'owner']);
        }

        $profile = $user->profiles()->firstOrCreate([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ], [
            'uuid' => Str::uuid()->toString(),
            'name' => $user->name,
            'age' => $age,
            'birth_date' => $birthDate,
            'sex' => $sex,
            'height' => $height,
            'notes' => $this->profileNotes($accountUsage),
        ]);

        if (! Payment::where('tenant_id', $tenant->id)->exists()) {
            Payment::create([
                'tenant_id' => $tenant->id,
                'amount' => 0,
                'status' => 'inactive',
                'payment_method' => 'none',
                'plan_type' => $accountUsage,
            ]);
        }

        return [
            'tenant' => $tenant,
            'profile' => $profile,
        ];
    }

    protected function profileNotes(string $accountUsage): string
    {
        return match ($accountUsage) {
            'family' => 'Acompanhamento familiar',
            'professional' => 'Acompanhamento profissional',
            default => 'Perfil pessoal',
        };
    }

    protected function uniqueTenantSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: Str::uuid()->toString();
        $slug = $baseSlug;
        $suffix = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}

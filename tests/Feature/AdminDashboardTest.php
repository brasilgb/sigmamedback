<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('dashboard counts only app users and exposes user type labels', function () {
    $this->withoutVite();

    $root = User::factory()->create([
        'name' => 'Root Admin',
        'email' => 'root@example.com',
        'is_admin' => true,
    ]);
    $personal = User::factory()->create([
        'name' => 'Personal User',
        'email' => 'personal@example.com',
        'is_admin' => false,
    ]);
    $family = User::factory()->create([
        'name' => 'Family User',
        'email' => 'family@example.com',
        'is_admin' => false,
    ]);

    $personalTenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Personal Tenant',
        'slug' => 'personal-tenant',
        'owner_id' => $personal->id,
        'account_usage' => 'personal',
    ]);
    $familyTenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Family Tenant',
        'slug' => 'family-tenant',
        'owner_id' => $family->id,
        'account_usage' => 'family',
    ]);

    $personalTenant->users()->attach($personal->id, ['role' => 'owner']);
    $familyTenant->users()->attach($family->id, ['role' => 'owner']);

    $response = $this->actingAs($root)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/dashboard')
        ->where('stats.total_users', 2)
        ->has('users.data', 3)
    );

    $users = collect($response->inertiaProps('users.data'))->keyBy('email');

    expect($users->get('root@example.com')['user_type'])->toBe('Root');
    expect($users->get('personal@example.com')['user_type'])->toBe('Pessoal');
    expect($users->get('family@example.com')['user_type'])->toBe('Familiar/cuidador');
});

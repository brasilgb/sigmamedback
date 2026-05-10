<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('mobile login returns account usage for non admin users', function () {
    $user = User::factory()->create([
        'email' => 'mobile@example.com',
        'password' => 'password',
        'is_admin' => false,
    ]);

    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test-login',
        'owner_id' => $user->id,
        'account_usage' => 'family',
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'mobile@example.com',
        'password' => 'password',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['data' => ['user', 'token', 'account_usage']]);
    $response->assertJsonPath('data.account_usage', 'family');
});

test('mobile login rejects root admin users', function () {
    User::factory()->create([
        'email' => 'root@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'root@example.com',
        'password' => 'password',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonPath('message', 'Credenciais inválidas.');
});

test('authenticated user endpoint returns account usage', function () {
    $user = User::factory()->create();

    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test-me',
        'owner_id' => $user->id,
        'account_usage' => 'personal',
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/auth/me');

    $response->assertOk();
    $response->assertJsonPath('data.account_usage', 'personal');
});

<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register with personal account usage', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'João Silva',
        'email' => 'joao@exemplo.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'account_usage' => 'personal',
        'age' => 35,
        'height' => 170,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.user.name', 'João Silva');
    $response->assertJsonStructure(['data' => ['user', 'tenant', 'profile', 'token']]);
    $this->assertDatabaseHas('users', [
        'name' => 'João Silva',
        'email' => 'joao@exemplo.com',
        'age' => 35,
        'is_admin' => false,
    ]);
    $this->assertDatabaseHas('profiles', [
        'name' => 'João Silva',
        'height' => 170,
        'notes' => 'Perfil pessoal',
    ]);
    $this->assertDatabaseHas('tenants', [
        'name' => 'João Silva',
        'account_usage' => 'personal',
        'sync_enabled' => false,
    ]);
    $this->assertDatabaseHas('payments', [
        'status' => 'inactive',
        'payment_method' => 'none',
        'plan_type' => 'personal',
    ]);
});

test('user can register with family account usage without creating accompanied person', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'João Silva',
        'email' => 'joao2@exemplo.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'account_usage' => 'family',
        'age' => null,
        'height' => null,
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['data' => ['user', 'tenant', 'profile', 'token']]);
    $response->assertJsonPath('data.tenant.account_usage', 'family');
    $response->assertJsonPath('data.profile.name', 'João Silva');
    $this->assertDatabaseHas('users', [
        'name' => 'João Silva',
        'email' => 'joao2@exemplo.com',
        'age' => null,
        'is_admin' => false,
    ]);
    $this->assertDatabaseHas('profiles', [
        'name' => 'João Silva',
        'height' => null,
        'notes' => 'Acompanhamento familiar',
    ]);
    $this->assertDatabaseHas('payments', [
        'status' => 'inactive',
        'payment_method' => 'none',
        'plan_type' => 'family',
    ]);
});

test('user can register with six character password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'João Silva',
        'email' => 'joao6@exemplo.com',
        'password' => '123456',
        'password_confirmation' => '123456',
        'account_usage' => 'personal',
        'age' => 35,
        'height' => 170,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.user.email', 'joao6@exemplo.com');
});

test('users with the same name receive unique tenant slugs', function () {
    $firstResponse = $this->postJson('/api/v1/auth/register', [
        'name' => 'João Silva',
        'email' => 'joao-slug-1@exemplo.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'account_usage' => 'personal',
    ]);

    $secondResponse = $this->postJson('/api/v1/auth/register', [
        'name' => 'João Silva',
        'email' => 'joao-slug-2@exemplo.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'account_usage' => 'personal',
    ]);

    $firstResponse->assertOk();
    $secondResponse->assertOk();

    expect($firstResponse->json('data.tenant.slug'))->toBe('joao-silva');
    expect($secondResponse->json('data.tenant.slug'))->toBe('joao-silva-2');
});

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
    $this->assertDatabaseHas('profiles', [
        'name' => 'João Silva',
        'height' => 170,
        'notes' => 'Perfil pessoal',
    ]);
});

test('user can register with family account usage and patient name', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'João Silva',
        'email' => 'joao2@exemplo.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'account_usage' => 'family',
        'patient_name' => 'Maria Silva',
        'height' => 165,
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['data' => ['user', 'tenant', 'profile', 'token']]);
    $this->assertDatabaseHas('profiles', [
        'name' => 'Maria Silva',
        'height' => 165,
        'notes' => 'Acompanhamento familiar',
    ]);
});

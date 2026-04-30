<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('mobile login accepts non admin users', function () {
    User::factory()->create([
        'email' => 'mobile@example.com',
        'password' => 'password',
        'is_admin' => false,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'mobile@example.com',
        'password' => 'password',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['data' => ['user', 'token']]);
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
    $response->assertJsonPath('message', 'Invalid credentials.');
});

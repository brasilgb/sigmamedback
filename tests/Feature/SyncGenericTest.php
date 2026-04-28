<?php

use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('sync push returns 403 when sync is not enabled', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
        'sync_enabled' => false,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/sync/push', [
            'resource' => 'blood-pressure',
            'items' => [],
        ]);

    $response->assertStatus(403);
    $response->assertJsonPath('message', 'Synchronization is not enabled for this account.');
});

test('sync push works when sync is enabled', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
        'sync_enabled' => true,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    $profile = Profile::create([
        'uuid' => Str::uuid()->toString(),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'name' => 'Test Profile',
    ]);

    Sanctum::actingAs($user, ['*']);

    $uuid = Str::uuid()->toString();
    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/sync/push', [
            'resource' => 'blood-pressure',
            'items' => [
                [
                    'uuid' => $uuid,
                    'profile_id' => $profile->id,
                    'systolic' => 120,
                    'diastolic' => 80,
                    'pulse' => 70,
                    'source' => 'manual',
                    'measured_at' => now()->toIso8601String(),
                ],
            ],
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('blood_pressure_readings', [
        'uuid' => $uuid,
        'systolic' => 120,
    ]);
});

test('sync push validates profile_id belongs to tenant', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
        'sync_enabled' => true,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    $otherTenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Other Tenant',
        'slug' => 'other-tenant',
        'owner_id' => $user->id,
    ]);

    $otherProfile = Profile::create([
        'uuid' => Str::uuid()->toString(),
        'tenant_id' => $otherTenant->id,
        'user_id' => $user->id,
        'name' => 'Other Profile',
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/sync/push', [
            'resource' => 'blood-pressure',
            'items' => [
                [
                    'uuid' => Str::uuid()->toString(),
                    'profile_id' => $otherProfile->id,
                    'systolic' => 120,
                    'diastolic' => 80,
                    'pulse' => 70,
                    'source' => 'manual',
                    'measured_at' => now()->toIso8601String(),
                ],
            ],
        ]);

    $response->assertStatus(422);
});

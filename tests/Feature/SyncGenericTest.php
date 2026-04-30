<?php

use App\Models\BloodPressureReading;
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
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('message', 'Blood-pressure push completed.');
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

test('sync push validates profile_id belongs to authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
        'sync_enabled' => true,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);
    $tenant->users()->attach($otherUser->id, ['role' => 'member']);

    $otherProfile = Profile::create([
        'uuid' => Str::uuid()->toString(),
        'tenant_id' => $tenant->id,
        'user_id' => $otherUser->id,
        'name' => 'Other User Profile',
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

    $response->assertUnprocessable();
});

test('sync pull returns only records from authenticated user profiles', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
        'sync_enabled' => true,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);
    $tenant->users()->attach($otherUser->id, ['role' => 'member']);

    $profile = Profile::create([
        'uuid' => Str::uuid()->toString(),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'name' => 'Test Profile',
    ]);
    $otherProfile = Profile::create([
        'uuid' => Str::uuid()->toString(),
        'tenant_id' => $tenant->id,
        'user_id' => $otherUser->id,
        'name' => 'Other User Profile',
    ]);

    $visibleUuid = Str::uuid()->toString();
    $hiddenUuid = Str::uuid()->toString();

    BloodPressureReading::create([
        'uuid' => $visibleUuid,
        'tenant_id' => $tenant->id,
        'profile_id' => $profile->id,
        'systolic' => 120,
        'diastolic' => 80,
        'pulse' => 70,
        'measured_at' => now(),
        'source' => 'manual',
    ]);
    BloodPressureReading::create([
        'uuid' => $hiddenUuid,
        'tenant_id' => $tenant->id,
        'profile_id' => $otherProfile->id,
        'systolic' => 130,
        'diastolic' => 85,
        'pulse' => 75,
        'measured_at' => now(),
        'source' => 'manual',
    ]);

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/sync/pull', [
            'resource' => 'blood-pressure',
        ]);

    $response->assertOk();
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('message', 'Blood-pressure pull completed.');

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.uuid'))->toBe($visibleUuid);
});

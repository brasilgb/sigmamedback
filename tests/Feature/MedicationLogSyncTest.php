<?php

use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('medication logs can sync with medication_uuid and soft delete via deleted_at', function () {
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

    $medication = Medication::create([
        'uuid' => Str::uuid()->toString(),
        'tenant_id' => $tenant->id,
        'profile_id' => $profile->id,
        'name' => 'Test med',
        'active' => true,
        'reminder_enabled' => false,
        'repeat_reminder_every_five_minutes' => false,
    ]);

    Sanctum::actingAs($user, ['*']);

    $payload = [
        'uuid' => Str::uuid()->toString(),
        'profile_id' => $profile->id,
        'medication_uuid' => $medication->uuid,
        'scheduled_at' => now()->addHour()->toISOString(),
        'taken_at' => now()->toISOString(),
        'status' => 'skipped',
        'deleted_at' => now()->toISOString(),
    ];

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/medication-logs/sync', ['items' => [$payload]]);

    $response->assertOk();

    $log = MedicationLog::withTrashed()->where('uuid', $payload['uuid'])->first();

    expect($log)->not->toBeNull();
    expect($log->medication_id)->toBe($medication->id);
    expect($log->scheduled_at)->not->toBeNull();
    expect($log->status)->toBe('skipped');
    expect($log->trashed())->toBeTrue();
    expect($log->deleted_at->format('Y-m-d H:i:s'))
        ->toBe(Carbon::parse($payload['deleted_at'])->format('Y-m-d H:i:s'));
});

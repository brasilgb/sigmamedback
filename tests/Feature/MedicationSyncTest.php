<?php

use App\Models\Medication;
use App\Models\Profile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('medication sync accepts full datetime scheduled_time and returns it in YYYY-MM-DD HH:mm:ss', function () {
    $user = User::factory()->create();

    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    $profile = Profile::create([
        'uuid' => Str::uuid()->toString(),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'name' => 'Test Profile',
    ]);

    Sanctum::actingAs($user, ['*']);

    $payload = [
        'uuid' => Str::uuid()->toString(),
        'profile_id' => $profile->id,
        'name' => 'Losartana',
        'dosage' => '50 mg',
        'instructions' => 'Take after breakfast',
        'active' => true,
        'scheduled_time' => '2026-04-26 21:00:00',
        'reminder_enabled' => true,
        'repeat_reminder_every_five_minutes' => false,
        'reminder_minutes_before' => 5,
        'notes' => 'Evening dose',
    ];

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/medications/sync', ['items' => [$payload]]);

    $response->assertOk();
    $response->assertJsonPath('data.0.scheduled_time', '2026-04-26 21:00:00');
    $response->assertJsonPath('data.0.name', 'Losartana');

    $medication = Medication::where('uuid', $payload['uuid'])->first();

    expect($medication)->not->toBeNull();
    expect($medication->scheduled_time->format('Y-m-d H:i:s'))->toBe('2026-04-26 21:00:00');
});

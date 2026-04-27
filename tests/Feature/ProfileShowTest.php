<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('profile endpoint returns a valid profile for authenticated tenant', function () {
    $user = User::factory()->create();

    $tenant = Tenant::create([
        'uuid' => Illuminate\Support\Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->getJson('/api/v1/profile');

    $response->assertOk();
    $response->assertJsonPath('data.tenant_id', $tenant->id);
    $response->assertJsonPath('data.user_id', $user->id);
    $response->assertJsonPath('message', 'Profile loaded.');
    $response->assertJsonStructure([
        'data' => [
            'id',
            'uuid',
            'tenant_id',
            'user_id',
            'name',
            'created_at',
            'updated_at',
        ],
        'meta',
        'message',
    ]);

    $this->assertDatabaseHas('profiles', [
        'id' => $response->json('data.id'),
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
    ]);
});

<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can submit feedback', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/feedback', [
            'rating' => 5,
            'comment' => 'Gostaria de uma tela para comparar evolução por mês.',
            'source' => 'home',
            'app_version' => '1.0.0',
            'platform' => 'android',
        ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'Feedback recebido.')
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.source', 'home');

    $this->assertDatabaseHas('feedback', [
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'rating' => 5,
        'source' => 'home',
        'app_version' => '1.0.0',
        'platform' => 'android',
    ]);
});

test('feedback requires rating or comment', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/feedback', [
            'source' => 'home',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['rating', 'comment']);
});

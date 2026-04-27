<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authenticated user can upload and delete avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    Sanctum::actingAs($user, ['*']);

    $avatar = File::image('avatar.png');

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/auth/me/avatar', ['avatar' => $avatar]);

    $response->assertOk();
    $response->assertJsonStructure(['data' => ['photo_path', 'avatar_url'], 'message']);

    $photoPath = $response->json('data.photo_path');

    Storage::disk('public')->assertExists($photoPath);

    $deleteResponse = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->deleteJson('/api/v1/auth/me/avatar');

    $deleteResponse->assertOk();
    Storage::disk('public')->assertMissing($photoPath);
});

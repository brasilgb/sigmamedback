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

    $avatar = File::image('avatar.png', 1200, 900);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/auth/me/avatar', ['avatar' => $avatar]);

    $response->assertOk();
    $response->assertJsonStructure(['data' => ['photo_path', 'avatar_url'], 'message']);

    $photoPath = $response->json('data.photo_path');
    $avatarUrl = config('app.url').'/'.$photoPath;

    Storage::disk('public')->assertExists($photoPath);
    expect($avatarUrl)
        ->toStartWith(config('app.url').'/avatars/')
        ->not->toContain('/storage/avatars/');
    expect($photoPath)->toEndWith('.jpg');
    $response->assertJsonPath('data.avatar_url', $avatarUrl);
    expect(getimagesize(Storage::disk('public')->path($photoPath)))->toMatchArray([
        0 => 512,
        1 => 512,
        2 => IMAGETYPE_JPEG,
    ]);

    $profileResponse = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->getJson('/api/v1/profile');

    $profileResponse->assertOk();
    $profileResponse->assertJsonPath('data.photo_path', $photoPath);
    $profileResponse->assertJsonPath('data.avatar_url', $avatarUrl);

    $meResponse = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->getJson('/api/v1/auth/me');

    $meResponse->assertOk();
    $meResponse->assertJsonPath('data.profile.photo_path', $photoPath);
    $meResponse->assertJsonPath('data.profile.avatar_url', $avatarUrl);

    $newAvatar = File::image('new-avatar.jpg', 900, 1200);

    $replaceResponse = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/auth/me/avatar', ['avatar' => $newAvatar]);

    $replaceResponse->assertOk();

    $newPhotoPath = $replaceResponse->json('data.photo_path');
    $newAvatarUrl = config('app.url').'/'.$newPhotoPath;

    expect($newPhotoPath)->not->toBe($photoPath);
    Storage::disk('public')->assertMissing($photoPath);
    Storage::disk('public')->assertExists($newPhotoPath);
    $replaceResponse->assertJsonPath('data.avatar_url', $newAvatarUrl);
    expect(getimagesize(Storage::disk('public')->path($newPhotoPath)))->toMatchArray([
        0 => 512,
        1 => 512,
        2 => IMAGETYPE_JPEG,
    ]);

    $deleteResponse = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->deleteJson('/api/v1/auth/me/avatar');

    $deleteResponse->assertOk();
    Storage::disk('public')->assertMissing($newPhotoPath);

    $profileResponse = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->getJson('/api/v1/profile');

    $profileResponse->assertOk();
    $profileResponse->assertJsonPath('data.photo_path', null);
    $profileResponse->assertJsonPath('data.avatar_url', null);
});

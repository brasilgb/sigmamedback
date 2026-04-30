<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('user can check sync access status', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
        'sync_enabled' => true,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->getJson('/api/v1/billing/sync-access');

    $response->assertOk();
    $response->assertJsonPath('data.sync_enabled', true);
    $response->assertJsonPath('data.status', 'active');
});

test('user can create checkout pix', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    $this->mock(MercadoPagoService::class, function (MockInterface $mock) {
        $mock->shouldReceive('createPixPayment')
            ->once()
            ->andReturn([
                'id' => 123456789,
                'date_of_expiration' => now()->addMinutes(30)->toIso8601String(),
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => '000201...',
                        'qr_code_base64' => 'iVBORw0KGgo...',
                    ],
                ],
            ]);
    });

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/billing/sync-access/checkout', [
            'plan' => 'sync_monthly',
        ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'payment_id',
            'status',
            'amount',
            'qr_code',
            'qr_code_base64',
            'expires_at',
        ],
    ]);
});

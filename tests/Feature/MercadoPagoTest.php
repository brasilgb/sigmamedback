<?php

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('checkout creates a pending payment in database', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
    ]);
    $tenant->users()->attach($user->id, ['role' => 'owner']);

    $mockResponse = [
        'id' => 123456789,
        'status' => 'pending',
        'date_of_expiration' => now()->addMinutes(30)->toIso8601String(),
        'point_of_interaction' => [
            'transaction_data' => [
                'qr_code' => '000201...',
                'qr_code_base64' => 'iVBORw0KGgo...',
            ],
        ],
    ];

    $this->mock(MercadoPagoService::class, function (MockInterface $mock) use ($mockResponse) {
        $mock->shouldReceive('createPixPayment')
            ->once()
            ->andReturn($mockResponse);
    });

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/billing/sync-access/checkout', [
            'plan' => 'family',
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('payments', [
        'tenant_id' => $tenant->id,
        'external_id' => '123456789',
        'status' => 'pending',
        'amount' => 19.90,
    ]);
});

test('webhook approves payment and enables tenant sync', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test',
        'owner_id' => $user->id,
        'sync_enabled' => false,
    ]);

    $payment = Payment::create([
        'tenant_id' => $tenant->id,
        'external_id' => '987654321',
        'amount' => 9.90,
        'status' => 'pending',
        'plan_type' => 'personal',
    ]);

    $mockPaymentResponse = [
        'id' => 987654321,
        'status' => 'approved',
    ];

    $this->mock(MercadoPagoService::class, function (MockInterface $mock) use ($mockPaymentResponse) {
        $mock->shouldReceive('getPayment')
            ->with('987654321')
            ->once()
            ->andReturn($mockPaymentResponse);
    });

    $response = $this->postJson('/api/v1/webhooks/mercadopago', [
        'type' => 'payment',
        'data' => ['id' => '987654321'],
    ]);

    $response->assertOk();

    $payment->refresh();
    $tenant->refresh();

    expect($payment->status)->toBe('approved');
    expect($tenant->sync_enabled)->toBeTrue();
});

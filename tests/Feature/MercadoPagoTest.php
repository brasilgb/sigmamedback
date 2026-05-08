<?php

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

test('mercado pago service logs failed pix payment response', function () {
    config(['services.mercadopago.access_token' => 'APP_USR-test-token']);

    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'message' => 'Forbidden',
            'error' => 'forbidden',
        ], 403, [
            'content-type' => 'application/json',
            'x-request-id' => 'request-123',
        ]),
    ]);

    Log::shouldReceive('error')
        ->withArgs(function (string $message, array $context): bool {
            expect($message)->toBe('Mercado Pago Payment Creation Failed');
            expect($context['status'])->toBe(403);
            expect($context['body'])->toBe([
                'message' => 'Forbidden',
                'error' => 'forbidden',
            ]);
            expect($context['raw_body'])->toBe('{"message":"Forbidden","error":"forbidden"}');
            expect($context['content_type'])->toContain('application/json');
            expect($context['x_request_id'])->toBe('request-123');

            return true;
        })
        ->once();

    $response = app(MercadoPagoService::class)->createPixPayment(
        9.90,
        'user@example.com',
        'Assinatura Meu Controle - Plano Personal',
    );

    expect($response)->toBeNull();
});

test('mercado pago service does not send local notification url', function () {
    config([
        'app.url' => 'http://172.16.8.75:8000',
        'services.mercadopago.access_token' => 'APP_USR-test-token',
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id' => 123456789,
        ]),
    ]);

    app(MercadoPagoService::class)->createPixPayment(
        9.90,
        'user@example.com',
        'Assinatura Meu Controle - Plano Personal',
    );

    Http::assertSent(fn ($request) => ! array_key_exists('notification_url', $request->data()));
});

test('mercado pago service sends public https notification url', function () {
    config([
        'app.url' => 'https://api.meucontrole.app',
        'services.mercadopago.access_token' => 'APP_USR-test-token',
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id' => 123456789,
        ]),
    ]);

    app(MercadoPagoService::class)->createPixPayment(
        9.90,
        'user@example.com',
        'Assinatura Meu Controle - Plano Personal',
    );

    Http::assertSent(fn ($request) => ($request->data()['notification_url'] ?? null) === 'https://api.meucontrole.app/api/v1/webhooks/mercadopago');
});

test('webhook approves payment and enables tenant sync', function () {
    config(['services.mercadopago.webhook_secret' => null]);

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

test('webhook rejects invalid signature when webhook secret is configured', function () {
    config(['services.mercadopago.webhook_secret' => 'secret']);

    $response = $this->withHeaders([
        'x-signature' => 'ts='.now()->timestamp.',v1=invalid',
        'x-request-id' => 'request-123',
    ])->postJson('/api/v1/webhooks/mercadopago?data.id=987654321', [
        'type' => 'payment',
        'data' => ['id' => '987654321'],
    ]);

    $response->assertUnauthorized();
    $response->assertJsonPath('message', 'Assinatura inválida.');
});

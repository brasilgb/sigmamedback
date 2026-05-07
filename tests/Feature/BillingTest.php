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

test('sync access refreshes approved pending pix payment', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test-refresh',
        'owner_id' => $user->id,
        'sync_enabled' => false,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    $payment = Payment::create([
        'tenant_id' => $tenant->id,
        'external_id' => 'approved-pix-id',
        'amount' => 9.90,
        'status' => 'pending',
        'plan_type' => 'personal_monthly',
        'qr_code' => 'qr-code',
        'qr_code_base64' => 'qr-code-base64',
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->mock(MercadoPagoService::class, function (MockInterface $mock) {
        $mock->shouldReceive('getPayment')
            ->with('approved-pix-id')
            ->once()
            ->andReturn([
                'id' => 'approved-pix-id',
                'status' => 'approved',
            ]);
    });

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->getJson('/api/v1/billing/sync-access');

    $response->assertOk();
    $response->assertJsonPath('data.sync_enabled', true);
    $response->assertJsonPath('data.status', 'active');

    $payment->refresh();
    $tenant->refresh();

    expect($payment->status)->toBe('approved');
    expect($payment->paid_at)->not->toBeNull();
    expect($tenant->sync_enabled)->toBeTrue();
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

    $this->mock(MercadoPagoService::class, function (MockInterface $mock) use ($user) {
        $mock->shouldReceive('createPixPayment')
            ->with(9.90, $user->email, 'Assinatura Meu Controle - Plano Pessoal')
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
            'plan' => 'personal_monthly',
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

test('checkout pix uses family caregiver payment description', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test-family',
        'owner_id' => $user->id,
        'account_usage' => 'family',
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    $this->mock(MercadoPagoService::class, function (MockInterface $mock) use ($user) {
        $mock->shouldReceive('createPixPayment')
            ->with(19.90, $user->email, 'Assinatura Meu Controle - Plano Familiar/Acompanhante')
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
            'plan' => 'family_caregiver_monthly',
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('payments', [
        'tenant_id' => $tenant->id,
        'amount' => 19.90,
        'plan_type' => 'family_caregiver_monthly',
    ]);
});

test('checkout reuses pending unexpired pix payment for same plan', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test-reuse',
        'owner_id' => $user->id,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    $payment = Payment::create([
        'tenant_id' => $tenant->id,
        'external_id' => 'existing-pix-id',
        'amount' => 9.90,
        'status' => 'pending',
        'plan_type' => 'personal_monthly',
        'qr_code' => 'existing-qr-code',
        'qr_code_base64' => 'existing-qr-code-base64',
        'expires_at' => now()->addMinutes(20),
    ]);

    $this->mock(MercadoPagoService::class, function (MockInterface $mock) {
        $mock->shouldNotReceive('createPixPayment');
    });

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/billing/sync-access/checkout', [
            'plan' => 'personal_monthly',
        ]);

    $response->assertOk();
    $response->assertJsonPath('message', 'Pagamento Pix pendente carregado.');
    $response->assertJsonPath('data.payment_id', $payment->external_id);
    $response->assertJsonPath('data.qr_code', $payment->qr_code);

    expect(Payment::where('tenant_id', $tenant->id)->count())->toBe(1);
});

test('checkout creates new pix when pending payment is expired', function () {
    $user = User::factory()->create();
    $tenant = Tenant::create([
        'uuid' => Str::uuid()->toString(),
        'name' => 'Tenant Test',
        'slug' => 'tenant-test-expired',
        'owner_id' => $user->id,
    ]);

    $tenant->users()->attach($user->id, ['role' => 'owner']);

    Payment::create([
        'tenant_id' => $tenant->id,
        'external_id' => 'expired-pix-id',
        'amount' => 9.90,
        'status' => 'pending',
        'plan_type' => 'personal_monthly',
        'qr_code' => 'expired-qr-code',
        'qr_code_base64' => 'expired-qr-code-base64',
        'expires_at' => now()->subMinute(),
    ]);

    $this->mock(MercadoPagoService::class, function (MockInterface $mock) use ($user) {
        $mock->shouldReceive('createPixPayment')
            ->with(9.90, $user->email, 'Assinatura Meu Controle - Plano Pessoal')
            ->once()
            ->andReturn([
                'id' => 987654321,
                'date_of_expiration' => now()->addMinutes(30)->toIso8601String(),
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => 'new-qr-code',
                        'qr_code_base64' => 'new-qr-code-base64',
                    ],
                ],
            ]);
    });

    Sanctum::actingAs($user, ['*']);

    $response = $this->withHeaders(['X-Tenant-Id' => $tenant->id])
        ->postJson('/api/v1/billing/sync-access/checkout', [
            'plan' => 'personal_monthly',
        ]);

    $response->assertOk();
    $response->assertJsonPath('message', 'Pagamento Pix criado.');
    $response->assertJsonPath('data.payment_id', '987654321');

    expect(Payment::where('tenant_id', $tenant->id)->count())->toBe(2);
});

test('checkout returns localized unauthenticated response without token', function () {
    $response = $this->postJson('/api/v1/billing/sync-access/checkout', [
        'plan' => 'personal',
    ]);

    $response->assertUnauthorized();
    $response->assertJsonPath('message', 'Não autenticado.');
});

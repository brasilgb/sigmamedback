<?php

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('user can request a password reset code without exposing account existence', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'joao@example.com',
    ]);

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'joao@example.com',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data', []);
    $response->assertJsonPath('message', 'Se o e-mail estiver cadastrado, enviaremos um código de recuperação.');

    Notification::assertSentTo($user, PasswordResetCodeNotification::class);

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'joao@example.com',
    ]);

    $unknownResponse = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'desconhecido@example.com',
    ]);

    $unknownResponse->assertOk();
    $unknownResponse->assertJsonPath('message', 'Se o e-mail estiver cadastrado, enviaremos um código de recuperação.');
});

test('user can reset password with valid code and login with new password', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'maria@example.com',
        'password' => 'old-password',
    ]);

    $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'maria@example.com',
    ])->assertOk();

    $sentCode = null;

    Notification::assertSentTo(
        $user,
        PasswordResetCodeNotification::class,
        function (PasswordResetCodeNotification $notification) use (&$sentCode): bool {
            $sentCode = $notification->code;

            return true;
        },
    );

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'maria@example.com',
        'code' => $sentCode,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data', []);
    $response->assertJsonPath('message', 'Senha redefinida com sucesso.');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();

    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => 'maria@example.com',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'maria@example.com',
        'password' => 'new-password',
    ])->assertOk();
});

test('password reset rejects invalid or expired codes', function () {
    $user = User::factory()->create([
        'email' => 'ana@example.com',
    ]);

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => Hash::make('123456'),
        'created_at' => now()->subMinutes(61),
    ]);

    $expiredResponse = $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'code' => '123456',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $expiredResponse->assertUnprocessable();
    $expiredResponse->assertJsonPath('message', 'Código de recuperação inválido ou expirado.');

    DB::table('password_reset_tokens')->update([
        'token' => Hash::make('654321'),
        'created_at' => now(),
    ]);

    $invalidResponse = $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'code' => '123456',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $invalidResponse->assertUnprocessable();
    $invalidResponse->assertJsonPath('message', 'Código de recuperação inválido ou expirado.');
});

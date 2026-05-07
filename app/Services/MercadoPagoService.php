<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MercadoPagoService
{
    protected ?string $accessToken;

    protected string $baseUrl = 'https://api.mercadopago.com/v1';

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token');
    }

    public function createPixPayment(float $amount, string $email, string $description): ?array
    {
        if (blank($this->accessToken)) {
            Log::error('Mercado Pago access token is not configured');

            return null;
        }

        try {
            $payload = [
                'transaction_amount' => $amount,
                'description' => $description,
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $email,
                ],
            ];

            if ($notificationUrl = $this->notificationUrl()) {
                $payload['notification_url'] = $notificationUrl;
            }

            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->withHeaders(['X-Idempotency-Key' => Str::uuid()->toString()])
                ->connectTimeout(5)
                ->timeout(15)
                ->retry(2, 200, throw: false)
                ->post("{$this->baseUrl}/payments", $payload);

            if ($response->failed()) {
                Log::error('Mercado Pago Payment Creation Failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'raw_body' => $response->body(),
                    'content_type' => $response->header('content-type'),
                    'x_request_id' => $response->header('x-request-id'),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Mercado Pago Service Error', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function notificationUrl(): ?string
    {
        $appUrl = config('app.url');

        if (! is_string($appUrl)) {
            return null;
        }

        $scheme = parse_url($appUrl, PHP_URL_SCHEME);
        $host = parse_url($appUrl, PHP_URL_HOST);

        if ($scheme !== 'https' || ! is_string($host)) {
            return null;
        }

        if ($this->isLocalHost($host)) {
            return null;
        }

        return rtrim($appUrl, '/').'/api/v1/webhooks/mercadopago';
    }

    protected function isLocalHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    public function getPayment(string $paymentId): ?array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->retry(2, 200, throw: false)
                ->get("{$this->baseUrl}/payments/{$paymentId}");

            if ($response->failed()) {
                Log::error('Mercado Pago Payment Lookup Failed', [
                    'payment_id' => $paymentId,
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'raw_body' => $response->body(),
                    'content_type' => $response->header('content-type'),
                    'x_request_id' => $response->header('x-request-id'),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Mercado Pago Payment Lookup Failed', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

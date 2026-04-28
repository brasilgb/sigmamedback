<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    protected string $accessToken;

    protected string $baseUrl = 'https://api.mercadopago.com/v1';

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token');
    }

    public function createPixPayment(float $amount, string $email, string $description)
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->post("{$this->baseUrl}/payments", [
                    'transaction_amount' => $amount,
                    'description' => $description,
                    'payment_method_id' => 'pix',
                    'payer' => [
                        'email' => $email,
                    ],
                    'notification_url' => config('app.url').'/api/v1/webhooks/mercadopago',
                ]);

            if ($response->failed()) {
                Log::error('Mercado Pago Payment Creation Failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Mercado Pago Service Error', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getPayment(string $paymentId)
    {
        $response = Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/payments/{$paymentId}");

        return $response->successful() ? $response->json() : null;
    }
}

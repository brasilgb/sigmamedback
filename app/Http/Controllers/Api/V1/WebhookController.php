<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function __construct(protected MercadoPagoService $mercadoPagoService) {}

    public function mercadopago(Request $request)
    {
        Log::info('Mercado Pago Webhook Received', $request->all());

        $type = $request->input('type');
        $dataId = $request->input('data.id') ?? $request->query('data.id') ?? $request->input('id');

        if ($type === 'payment' || $request->has('id')) {
            if (! $dataId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento não informado.',
                ], 400);
            }

            if (! $this->hasValidSignature($request, (string) $dataId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assinatura inválida.',
                ], 401);
            }

            $this->processPayment((string) $dataId);
        }

        return response()->json(['success' => true]);
    }

    protected function processPayment(string $paymentId)
    {
        $mpPayment = $this->mercadoPagoService->getPayment($paymentId);

        if (! $mpPayment) {
            Log::warning('Payment not found in Mercado Pago API', ['id' => $paymentId]);

            return;
        }

        $payment = Payment::where('external_id', $paymentId)->first();

        if (! $payment) {
            Log::warning('Payment not found in local database', ['external_id' => $paymentId]);

            return;
        }

        $status = $mpPayment['status'];

        if ($status === 'approved' && $payment->status !== 'approved') {
            $payment->update([
                'status' => 'approved',
                'paid_at' => now(),
            ]);

            $payment->tenant->update(['sync_enabled' => true]);

            Log::info('Payment approved and sync enabled', [
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
            ]);
        } else {
            $payment->update(['status' => $status]);
        }
    }

    protected function hasValidSignature(Request $request, string $dataId): bool
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (! $secret) {
            return true;
        }

        $signature = $request->header('x-signature');
        $requestId = $request->header('x-request-id');

        if (! $signature || ! $requestId) {
            return false;
        }

        $parts = collect(explode(',', $signature))
            ->mapWithKeys(function (string $part): array {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

                return $key && $value ? [trim($key) => trim($value)] : [];
            });

        $timestamp = $parts->get('ts');
        $hash = $parts->get('v1');

        if (! $timestamp || ! $hash) {
            return false;
        }

        $timestampSeconds = strlen($timestamp) > 10
            ? (int) floor(((int) $timestamp) / 1000)
            : (int) $timestamp;

        if (abs(now()->timestamp - $timestampSeconds) > 300) {
            return false;
        }

        $signatureDataId = (string) $request->query('data.id', $dataId);

        if (ctype_alnum($signatureDataId)) {
            $signatureDataId = Str::lower($signatureDataId);
        }

        $manifest = "id:{$signatureDataId};request-id:{$requestId};ts:{$timestamp};";
        $expectedHash = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expectedHash, $hash);
    }
}

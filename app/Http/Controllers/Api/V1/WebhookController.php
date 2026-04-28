<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(protected MercadoPagoService $mercadoPagoService) {}

    public function mercadopago(Request $request)
    {
        Log::info('Mercado Pago Webhook Received', $request->all());

        $type = $request->input('type');
        $dataId = $request->input('data.id') ?? $request->input('id');

        if ($type === 'payment' || $request->has('id')) {
            $this->processPayment($dataId);
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

            // Enable sync for the tenant
            $payment->tenant->update(['sync_enabled' => true]);

            Log::info('Payment approved and sync enabled', [
                'payment_id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
            ]);
        } else {
            $payment->update(['status' => $status]);
        }
    }
}

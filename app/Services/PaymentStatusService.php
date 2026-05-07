<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentStatusService
{
    public function __construct(protected MercadoPagoService $mercadoPagoService) {}

    public function refreshPendingForTenant(int $tenantId): void
    {
        Payment::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->whereNotNull('external_id')
            ->latest()
            ->limit(5)
            ->get()
            ->each(fn (Payment $payment) => $this->refresh($payment));
    }

    public function refreshRecentPending(int $limit = 20): void
    {
        Payment::where('status', 'pending')
            ->whereNotNull('external_id')
            ->latest()
            ->limit($limit)
            ->get()
            ->each(fn (Payment $payment) => $this->refresh($payment));
    }

    public function refresh(Payment $payment): void
    {
        if (! $payment->external_id) {
            return;
        }

        $mpPayment = $this->mercadoPagoService->getPayment($payment->external_id);

        if (! $mpPayment) {
            Log::warning('Payment not found in Mercado Pago API', ['id' => $payment->external_id]);

            return;
        }

        $this->applyMercadoPagoStatus($payment, $mpPayment);
    }

    public function applyMercadoPagoStatus(Payment $payment, array $mpPayment): void
    {
        $status = $mpPayment['status'] ?? null;

        if (! is_string($status)) {
            Log::warning('Mercado Pago payment status not found', [
                'payment_id' => $payment->id,
                'external_id' => $payment->external_id,
            ]);

            return;
        }

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

            return;
        }

        if ($payment->status !== $status) {
            $payment->update(['status' => $status]);
        }
    }
}

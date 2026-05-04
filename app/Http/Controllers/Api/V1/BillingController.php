<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(protected MercadoPagoService $mercadoPagoService) {}

    public function syncAccess(Request $request)
    {
        $tenant = TenantContext::current();

        return $this->successResponse([
            'sync_enabled' => $tenant->sync_enabled,
            'status' => $tenant->sync_enabled ? 'active' : 'inactive',
            'expires_at' => $tenant->sync_enabled ? now()->addMonth()->toIso8601String() : null,
            'provider' => 'mercado_pago',
        ], 'Acesso à sincronização carregado.');
    }

    public function checkout(Request $request)
    {
        $tenant = TenantContext::current();
        $user = $request->user();
        $planType = $request->input('plan', 'personal');

        $amounts = [
            'personal' => 9.90,
            'family' => 19.90,
        ];

        $amount = $amounts[$planType] ?? 9.90;
        $description = 'Assinatura SigmaMed - Plano '.ucfirst($planType);

        $mpResponse = $this->mercadoPagoService->createPixPayment(
            $amount,
            $user->email,
            $description
        );

        if (! $mpResponse) {
            return $this->errorResponse('Não foi possível criar o pagamento no Mercado Pago.', 500);
        }

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'external_id' => (string) $mpResponse['id'],
            'amount' => $amount,
            'status' => 'pending',
            'plan_type' => $planType,
            'qr_code' => $mpResponse['point_of_interaction']['transaction_data']['qr_code'],
            'qr_code_base64' => $mpResponse['point_of_interaction']['transaction_data']['qr_code_base64'],
            'expires_at' => $mpResponse['date_of_expiration'],
        ]);

        return $this->successResponse([
            'payment_id' => $payment->external_id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'qr_code' => $payment->qr_code,
            'qr_code_base64' => $payment->qr_code_base64,
            'expires_at' => $payment->expires_at->toIso8601String(),
        ], 'Pagamento Pix criado.');
    }
}

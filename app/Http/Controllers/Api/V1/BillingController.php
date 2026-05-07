<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use App\Services\PaymentStatusService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        protected MercadoPagoService $mercadoPagoService,
        protected PaymentStatusService $paymentStatusService,
    ) {}

    public function syncAccess(Request $request)
    {
        $tenant = TenantContext::current();

        $this->paymentStatusService->refreshPendingForTenant($tenant->id);
        $tenant->refresh();

        return $this->successResponse([
            'sync_enabled' => $tenant->sync_enabled,
            'status' => $tenant->sync_enabled ? 'active' : 'inactive',
            'expires_at' => null,
            'provider' => 'mercado_pago',
        ], 'Acesso à sincronização carregado.');
    }

    public function checkout(Request $request)
    {
        $tenant = TenantContext::current();
        $user = $request->user();
        $planType = $request->input('plan', $tenant->account_usage === 'family' ? 'family_caregiver_monthly' : 'personal_monthly');
        $plan = $this->planDetails($planType);

        $amount = $plan['amount'];
        $description = 'Assinatura Meu Controle - Plano '.$plan['label'];

        $payment = Payment::where('tenant_id', $tenant->id)
            ->where('plan_type', $planType)
            ->where('status', 'pending')
            ->whereNotNull('external_id')
            ->whereNotNull('qr_code')
            ->whereNotNull('qr_code_base64')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($payment instanceof Payment) {
            return $this->paymentResponse($payment, 'Pagamento Pix pendente carregado.');
        }

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

        return $this->paymentResponse($payment, 'Pagamento Pix criado.');
    }

    protected function paymentResponse(Payment $payment, string $message): JsonResponse
    {
        return $this->successResponse([
            'payment_id' => $payment->external_id,
            'status' => $payment->display_status,
            'raw_status' => $payment->status,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'qr_code' => $payment->qr_code,
            'qr_code_base64' => $payment->qr_code_base64,
            'expires_at' => $payment->expires_at->toIso8601String(),
        ], $message);
    }

    /**
     * @return array{amount: float, label: string}
     */
    protected function planDetails(string $planType): array
    {
        return match ($planType) {
            'personal', 'personal_monthly' => [
                'amount' => 9.90,
                'label' => 'Pessoal',
            ],
            'personal_annual' => [
                'amount' => 99.90,
                'label' => 'Pessoal',
            ],
            'family', 'family_caregiver_monthly' => [
                'amount' => 19.90,
                'label' => 'Familiar/Acompanhante',
            ],
            'family_caregiver_annual' => [
                'amount' => 199.90,
                'label' => 'Familiar/Acompanhante',
            ],
            default => [
                'amount' => 9.90,
                'label' => 'Pessoal',
            ],
        };
    }
}

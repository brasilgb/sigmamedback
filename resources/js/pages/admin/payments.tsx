import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface Payment {
    id: number;
    external_id: string;
    amount: number;
    status: string;
    display_status: string;
    plan_type: string;
    created_at: string;
    expires_at: string | null;
    paid_at: string | null;
    tenant: {
        name: string;
        owner: {
            name: string;
            email: string;
        };
    };
}

interface Props {
    payments: {
        data: Payment[];
        links: any[];
    };
}

const Payments: React.FC<Props> = ({ payments }) => {
    const getPlanLabel = (planType: string) => {
        switch (planType) {
            case 'personal':
            case 'personal_monthly':
                return 'Pessoal mensal';
            case 'personal_annual':
                return 'Pessoal anual';
            case 'family':
            case 'family_caregiver_monthly':
                return 'Familiar/Acompanhante mensal';
            case 'family_caregiver_annual':
                return 'Familiar/Acompanhante anual';
            default:
                return planType || 'Não informado';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'approved':
                return 'Aprovado';
            case 'pending':
                return 'Pendente';
            case 'rejected':
                return 'Rejeitado';
            case 'cancelled':
                return 'Cancelado';
            case 'expired':
                return 'Expirado';
            case 'inactive':
                return 'Inativo';
            default:
                return status || 'Não informado';
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved':
                return 'bg-emerald-500/20 text-emerald-400';
            case 'pending':
                return 'bg-orange-500/20 text-orange-400';
            case 'rejected':
                return 'bg-red-500/20 text-red-400';
            case 'cancelled':
                return 'bg-red-500/20 text-red-400';
            case 'expired':
                return 'bg-gray-700 text-gray-300';
            case 'inactive':
                return 'bg-gray-700 text-gray-400';
            default:
                return 'bg-gray-700 text-gray-400';
        }
    };

    return (
        <div className="min-h-screen bg-gray-900 p-8 font-sans text-gray-100">
            <Head title="Histórico de Pagamentos - SigmaMed" />

            <div className="mx-auto max-w-7xl">
                <header className="mb-12 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <img
                            src="/images/logo_branco.png"
                            alt="SigmaMed"
                            className="h-14 w-auto"
                        />
                        <div>
                            <h1 className="bg-gradient-to-r from-orange-400 to-red-400 bg-clip-text text-4xl font-extrabold text-transparent">
                                Pagamentos
                            </h1>
                            <p className="mt-2 text-gray-400">
                                Histórico de transações via Pix
                            </p>
                        </div>
                    </div>
                    <nav className="flex gap-4">
                        <Link
                            href="/admin"
                            className="rounded-lg bg-gray-800 px-4 py-2 transition-colors hover:bg-gray-700"
                        >
                            Dashboard
                        </Link>
                        <Link
                            href="/admin/feedbacks"
                            className="rounded-lg bg-gray-800 px-4 py-2 transition-colors hover:bg-gray-700"
                        >
                            Feedbacks
                        </Link>
                    </nav>
                </header>

                <div className="overflow-hidden rounded-2xl border border-gray-700 bg-gray-800 shadow-xl">
                    <table className="w-full text-left">
                        <thead>
                            <tr className="bg-gray-900/50 text-sm text-gray-400">
                                <th className="px-6 py-4 font-medium">
                                    Usuário / Tenant
                                </th>
                                <th className="px-6 py-4 font-medium">Plano</th>
                                <th className="px-6 py-4 font-medium">Valor</th>
                                <th className="px-6 py-4 font-medium">
                                    Status
                                </th>
                                <th className="px-6 py-4 font-medium">
                                    ID Mercado Pago
                                </th>
                                <th className="px-6 py-4 font-medium">Data</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-700">
                            {payments.data.map((payment) => {
                                const displayStatus =
                                    payment.display_status || payment.status;

                                return (
                                    <tr
                                        key={payment.id}
                                        className="transition-colors hover:bg-gray-700/30"
                                    >
                                        <td className="px-6 py-4">
                                            <div className="font-medium">
                                                {payment.tenant.owner.name}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {payment.tenant.name}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            {getPlanLabel(payment.plan_type)}
                                        </td>
                                        <td className="px-6 py-4 font-bold">
                                            R${' '}
                                            {parseFloat(
                                                payment.amount.toString(),
                                            ).toLocaleString('pt-BR', {
                                                minimumFractionDigits: 2,
                                            })}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span
                                                className={`rounded-md px-2 py-1 text-xs font-bold ${getStatusColor(displayStatus)}`}
                                            >
                                                {getStatusLabel(displayStatus)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 font-mono text-xs text-gray-500">
                                            {payment.external_id}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-400">
                                            {new Date(
                                                payment.created_at,
                                            ).toLocaleString('pt-BR')}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default Payments;

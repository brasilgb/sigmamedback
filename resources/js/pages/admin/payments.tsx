import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface Payment {
    id: number;
    external_id: string;
    amount: number;
    status: string;
    plan_type: string;
    created_at: string;
    paid_at: string | null;
    tenant: {
        name: string;
        owner: {
            name: string;
            email: string;
        }
    }
}

interface Props {
    payments: {
        data: Payment[];
        links: any[];
    };
}

const Payments: React.FC<Props> = ({ payments }) => {
    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved': return 'bg-emerald-500/20 text-emerald-400';
            case 'pending': return 'bg-orange-500/20 text-orange-400';
            case 'rejected': return 'bg-red-500/20 text-red-400';
            default: return 'bg-gray-700 text-gray-400';
        }
    };

    return (
        <div className="min-h-screen bg-gray-900 text-gray-100 p-8 font-sans">
            <Head title="Histórico de Pagamentos - SigmaMed" />
            
            <div className="max-w-7xl mx-auto">
                <header className="flex justify-between items-center mb-12">
                    <div>
                        <h1 className="text-4xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-orange-400 to-red-400">
                            Pagamentos
                        </h1>
                        <p className="text-gray-400 mt-2">Histórico de transações via Pix</p>
                    </div>
                    <nav className="flex gap-4">
                        <Link href="/admin" className="px-4 py-2 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors">
                            Dashboard
                        </Link>
                        <Link href="/admin/feedbacks" className="px-4 py-2 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors">
                            Feedbacks
                        </Link>
                    </nav>
                </header>

                <div className="bg-gray-800 rounded-2xl border border-gray-700 shadow-xl overflow-hidden">
                    <table className="w-full text-left">
                        <thead>
                            <tr className="bg-gray-900/50 text-gray-400 text-sm">
                                <th className="px-6 py-4 font-medium">Usuário / Tenant</th>
                                <th className="px-6 py-4 font-medium">Plano</th>
                                <th className="px-6 py-4 font-medium">Valor</th>
                                <th className="px-6 py-4 font-medium">Status</th>
                                <th className="px-6 py-4 font-medium">ID Mercado Pago</th>
                                <th className="px-6 py-4 font-medium">Data</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-700">
                            {payments.data.map(payment => (
                                <tr key={payment.id} className="hover:bg-gray-700/30 transition-colors">
                                    <td className="px-6 py-4">
                                        <div className="font-medium">{payment.tenant.owner.name}</div>
                                        <div className="text-xs text-gray-500">{payment.tenant.name}</div>
                                    </td>
                                    <td className="px-6 py-4 capitalize">{payment.plan_type}</td>
                                    <td className="px-6 py-4 font-bold">R$ {parseFloat(payment.amount.toString()).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                                    <td className="px-6 py-4">
                                        <span className={`px-2 py-1 rounded-md text-xs font-bold ${getStatusColor(payment.status)}`}>
                                            {payment.status.toUpperCase()}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-xs font-mono text-gray-500">{payment.external_id}</td>
                                    <td className="px-6 py-4 text-gray-400 text-sm">
                                        {new Date(payment.created_at).toLocaleString('pt-BR')}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default Payments;

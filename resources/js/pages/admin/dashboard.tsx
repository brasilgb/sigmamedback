import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';

interface Stat {
    total_users: number;
    active_subscriptions: number;
    total_revenue: number;
    pending_payments: number;
}

interface Tenant {
    id: number;
    name: string;
    account_usage?: string;
    sync_enabled: boolean;
}

interface User {
    id: number;
    name: string;
    email: string;
    user_type: string;
    tenants: Tenant[];
    created_at: string;
}

interface Props {
    stats: Stat;
    users: {
        data: User[];
        links: any[];
    };
}

const Dashboard: React.FC<Props> = ({ stats, users }) => {
    const [editingUser, setEditingUser] = useState<User | null>(null);

    const userTypeClasses = (userType: string) => {
        if (userType === 'Root') {
            return 'bg-red-500/20 text-red-300 border-red-500/30';
        }

        if (userType === 'Familiar/cuidador') {
            return 'bg-amber-500/20 text-amber-300 border-amber-500/30';
        }

        if (userType === 'Profissional') {
            return 'bg-violet-500/20 text-violet-300 border-violet-500/30';
        }

        return 'bg-blue-500/20 text-blue-300 border-blue-500/30';
    };

    const { data, setData, put, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
    });

    const handleEdit = (user: User) => {
        setEditingUser(user);
        setData({
            name: user.name,
            email: user.email,
        });
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingUser) return;

        put(`/admin/users/${editingUser.id}`, {
            onSuccess: () => {
                setEditingUser(null);
                reset();
            },
        });
    };

    const toggleSync = (tenantId: number) => {
        router.post(`/admin/tenants/${tenantId}/toggle-sync`);
    };

    const handleDelete = (userId: number, name: string) => {
        if (
            confirm(
                `Tem certeza que deseja excluir o usuário ${name}? Esta ação não pode ser desfeita.`,
            )
        ) {
            router.delete(`/admin/users/${userId}`);
        }
    };

    return (
        <div className="min-h-screen bg-gray-900 p-8 font-sans text-gray-100">
            <Head title="Admin Dashboard - SigmaMed" />

            <div className="mx-auto max-w-7xl">
                <header className="mb-12 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <img
                            src="/images/logo_branco.png"
                            alt="SigmaMed"
                            className="h-14 w-auto"
                        />
                        <div>
                            <h1 className="bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-4xl font-extrabold text-transparent">
                                SigmaMed Admin
                            </h1>
                            <p className="mt-2 text-gray-400">
                                Gerenciamento de usuários e assinaturas
                            </p>
                        </div>
                    </div>
                    <nav className="flex gap-4">
                        <Link
                            href="/admin/payments"
                            className="rounded-lg bg-gray-800 px-4 py-2 transition-colors hover:bg-gray-700"
                        >
                            Pagamentos
                        </Link>
                        <Link
                            href="/admin/feedbacks"
                            className="rounded-lg bg-gray-800 px-4 py-2 transition-colors hover:bg-gray-700"
                        >
                            Feedbacks
                        </Link>
                        <Link
                            href="/admin/logout"
                            method="post"
                            as="button"
                            className="rounded-lg border border-gray-700 px-4 py-2 transition-colors hover:bg-gray-800"
                        >
                            Sair
                        </Link>
                    </nav>
                </header>

                <div className="mb-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-2xl border border-gray-700 bg-gray-800 p-6 shadow-xl transition-transform hover:scale-105">
                        <p className="mb-1 text-sm text-gray-400">
                            Usuários do App
                        </p>
                        <p className="text-3xl font-bold">
                            {stats.total_users}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-gray-700 bg-gray-800 p-6 shadow-xl transition-transform hover:scale-105">
                        <p className="mb-1 text-sm text-gray-400">
                            Assinaturas Ativas
                        </p>
                        <p className="text-3xl font-bold text-emerald-400">
                            {stats.active_subscriptions}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-gray-700 bg-gray-800 p-6 shadow-xl transition-transform hover:scale-105">
                        <p className="mb-1 text-sm text-gray-400">
                            Receita Total
                        </p>
                        <p className="text-3xl font-bold text-blue-400">
                            R${' '}
                            {parseFloat(
                                stats.total_revenue.toString(),
                            ).toLocaleString('pt-BR')}
                        </p>
                    </div>
                    <div className="rounded-2xl border border-gray-700 bg-gray-800 p-6 shadow-xl transition-transform hover:scale-105">
                        <p className="mb-1 text-sm text-gray-400">
                            Pagamentos Pendentes
                        </p>
                        <p className="text-3xl font-bold text-orange-400">
                            {stats.pending_payments}
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-gray-700 bg-gray-800 shadow-xl">
                    <div className="border-b border-gray-700 p-6">
                        <h2 className="text-xl font-bold">Usuários Recentes</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead>
                                <tr className="bg-gray-900/50 text-sm text-gray-400">
                                    <th className="px-6 py-4 font-medium">
                                        Nome
                                    </th>
                                    <th className="px-6 py-4 font-medium">
                                        Email
                                    </th>
                                    <th className="px-6 py-4 font-medium">
                                        Tipo
                                    </th>
                                    <th className="px-6 py-4 font-medium">
                                        Tenant/Plano
                                    </th>
                                    <th className="px-6 py-4 font-medium">
                                        Data Cadastro
                                    </th>
                                    <th className="px-6 py-4 text-right font-medium">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-700">
                                {users.data.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="group transition-colors hover:bg-gray-700/30"
                                    >
                                        <td className="px-6 py-4 font-medium">
                                            {user.name}
                                        </td>
                                        <td className="px-6 py-4 text-gray-400">
                                            {user.email}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span
                                                className={`inline-flex rounded-md border px-2 py-1 text-xs font-bold whitespace-nowrap ${userTypeClasses(user.user_type)}`}
                                            >
                                                {user.user_type}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            {user.tenants.map((t) => (
                                                <div
                                                    key={t.id}
                                                    className="flex items-center gap-3"
                                                >
                                                    <span
                                                        className={`rounded-md px-2 py-1 text-xs font-bold ${t.sync_enabled ? 'bg-emerald-500/20 text-emerald-400' : 'bg-gray-700 text-gray-400'}`}
                                                    >
                                                        {t.name}
                                                    </span>
                                                    <button
                                                        onClick={() =>
                                                            toggleSync(t.id)
                                                        }
                                                        className={`text-[10px] font-bold tracking-wider uppercase hover:underline ${t.sync_enabled ? 'text-red-400' : 'text-emerald-400'}`}
                                                    >
                                                        {t.sync_enabled
                                                            ? 'Desativar'
                                                            : 'Ativar Sync'}
                                                    </button>
                                                </div>
                                            ))}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-400">
                                            {new Date(
                                                user.created_at,
                                            ).toLocaleDateString('pt-BR')}
                                        </td>
                                        <td className="space-x-3 px-6 py-4 text-right">
                                            <button
                                                onClick={() => handleEdit(user)}
                                                className="text-blue-400 opacity-0 transition-colors group-hover:opacity-100 hover:text-blue-300"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                onClick={() =>
                                                    handleDelete(
                                                        user.id,
                                                        user.name,
                                                    )
                                                }
                                                className="text-red-400 opacity-0 transition-colors group-hover:opacity-100 hover:text-red-300"
                                            >
                                                Excluir
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Edit Modal */}
            {editingUser && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-md rounded-3xl border border-gray-700 bg-gray-800 p-8 shadow-2xl">
                        <h3 className="mb-6 text-2xl font-bold">
                            Editar Usuário
                        </h3>
                        <form onSubmit={handleUpdate} className="space-y-4">
                            <div>
                                <label className="mb-2 block text-sm text-gray-400">
                                    Nome
                                </label>
                                <input
                                    type="text"
                                    className="w-full rounded-xl border border-gray-700 bg-gray-900 p-3 outline-none focus:ring-2 focus:ring-blue-500"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                />
                                {errors.name && (
                                    <p className="mt-1 text-xs text-red-400">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="mb-2 block text-sm text-gray-400">
                                    Email
                                </label>
                                <input
                                    type="email"
                                    className="w-full rounded-xl border border-gray-700 bg-gray-900 p-3 outline-none focus:ring-2 focus:ring-blue-500"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                />
                                {errors.email && (
                                    <p className="mt-1 text-xs text-red-400">
                                        {errors.email}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="mb-2 block text-sm text-gray-400">
                                    Nova Senha (opcional)
                                </label>
                                <input
                                    type="password"
                                    className="w-full rounded-xl border border-gray-700 bg-gray-900 p-3 outline-none focus:ring-2 focus:ring-blue-500"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                    placeholder="Deixe em branco para manter a atual"
                                />
                                {errors.password && (
                                    <p className="mt-1 text-xs text-red-400">
                                        {errors.password}
                                    </p>
                                )}
                            </div>
                            <div className="mt-8 flex gap-4">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex-1 rounded-xl bg-blue-600 py-3 font-bold transition-colors hover:bg-blue-500 disabled:opacity-50"
                                >
                                    {processing
                                        ? 'Salvando...'
                                        : 'Salvar Alterações'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setEditingUser(null)}
                                    className="flex-1 rounded-xl bg-gray-700 py-3 font-bold transition-colors hover:bg-gray-600"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Dashboard;

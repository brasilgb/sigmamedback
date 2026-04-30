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
        if (confirm(`Tem certeza que deseja excluir o usuário ${name}? Esta ação não pode ser desfeita.`)) {
            router.delete(`/admin/users/${userId}`);
        }
    };

    return (
        <div className="min-h-screen bg-gray-900 text-gray-100 p-8 font-sans">
            <Head title="Admin Dashboard - SigmaMed" />
            
            <div className="max-w-7xl mx-auto">
                <header className="flex justify-between items-center mb-12">
                    <div>
                        <h1 className="text-4xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">
                            SigmaMed Admin
                        </h1>
                        <p className="text-gray-400 mt-2">Gerenciamento de usuários e assinaturas</p>
                    </div>
                    <nav className="flex gap-4">
                        <Link href="/admin/payments" className="px-4 py-2 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors">
                            Pagamentos
                        </Link>
                        <Link href="/admin/logout" method="post" as="button" className="px-4 py-2 rounded-lg border border-gray-700 hover:bg-gray-800 transition-colors">
                            Sair
                        </Link>
                    </nav>
                </header>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                    <div className="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl hover:scale-105 transition-transform">
                        <p className="text-gray-400 text-sm mb-1">Usuários do App</p>
                        <p className="text-3xl font-bold">{stats.total_users}</p>
                    </div>
                    <div className="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl hover:scale-105 transition-transform">
                        <p className="text-gray-400 text-sm mb-1">Assinaturas Ativas</p>
                        <p className="text-3xl font-bold text-emerald-400">{stats.active_subscriptions}</p>
                    </div>
                    <div className="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl hover:scale-105 transition-transform">
                        <p className="text-gray-400 text-sm mb-1">Receita Total</p>
                        <p className="text-3xl font-bold text-blue-400">R$ {parseFloat(stats.total_revenue.toString()).toLocaleString('pt-BR')}</p>
                    </div>
                    <div className="bg-gray-800 p-6 rounded-2xl border border-gray-700 shadow-xl hover:scale-105 transition-transform">
                        <p className="text-gray-400 text-sm mb-1">Pagamentos Pendentes</p>
                        <p className="text-3xl font-bold text-orange-400">{stats.pending_payments}</p>
                    </div>
                </div>

                <div className="bg-gray-800 rounded-2xl border border-gray-700 shadow-xl overflow-hidden">
                    <div className="p-6 border-b border-gray-700">
                        <h2 className="text-xl font-bold">Usuários Recentes</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead>
                                <tr className="bg-gray-900/50 text-gray-400 text-sm">
                                    <th className="px-6 py-4 font-medium">Nome</th>
                                    <th className="px-6 py-4 font-medium">Email</th>
                                    <th className="px-6 py-4 font-medium">Tipo</th>
                                    <th className="px-6 py-4 font-medium">Tenant/Plano</th>
                                    <th className="px-6 py-4 font-medium">Data Cadastro</th>
                                    <th className="px-6 py-4 font-medium text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-700">
                                {users.data.map(user => (
                                    <tr key={user.id} className="hover:bg-gray-700/30 transition-colors group">
                                        <td className="px-6 py-4 font-medium">{user.name}</td>
                                        <td className="px-6 py-4 text-gray-400">{user.email}</td>
                                        <td className="px-6 py-4">
                                            <span className={`inline-flex whitespace-nowrap rounded-md border px-2 py-1 text-xs font-bold ${userTypeClasses(user.user_type)}`}>
                                                {user.user_type}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4">
                                            {user.tenants.map(t => (
                                                <div key={t.id} className="flex items-center gap-3">
                                                    <span className={`px-2 py-1 rounded-md text-xs font-bold ${t.sync_enabled ? 'bg-emerald-500/20 text-emerald-400' : 'bg-gray-700 text-gray-400'}`}>
                                                        {t.name}
                                                    </span>
                                                    <button 
                                                        onClick={() => toggleSync(t.id)}
                                                        className={`text-[10px] uppercase font-bold tracking-wider hover:underline ${t.sync_enabled ? 'text-red-400' : 'text-emerald-400'}`}
                                                    >
                                                        {t.sync_enabled ? 'Desativar' : 'Ativar Sync'}
                                                    </button>
                                                </div>
                                            ))}
                                        </td>
                                        <td className="px-6 py-4 text-gray-400 text-sm">
                                            {new Date(user.created_at).toLocaleDateString('pt-BR')}
                                        </td>
                                        <td className="px-6 py-4 text-right space-x-3">
                                            <button 
                                                onClick={() => handleEdit(user)}
                                                className="text-blue-400 hover:text-blue-300 transition-colors opacity-0 group-hover:opacity-100"
                                            >
                                                Editar
                                            </button>
                                            <button 
                                                onClick={() => handleDelete(user.id, user.name)}
                                                className="text-red-400 hover:text-red-300 transition-colors opacity-0 group-hover:opacity-100"
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
                <div className="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50 backdrop-blur-sm">
                    <div className="bg-gray-800 border border-gray-700 p-8 rounded-3xl w-full max-w-md shadow-2xl">
                        <h3 className="text-2xl font-bold mb-6">Editar Usuário</h3>
                        <form onSubmit={handleUpdate} className="space-y-4">
                            <div>
                                <label className="block text-sm text-gray-400 mb-2">Nome</label>
                                <input 
                                    type="text" 
                                    className="w-full bg-gray-900 border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 outline-none"
                                    value={data.name}
                                    onChange={e => setData('name', e.target.value)}
                                />
                                {errors.name && <p className="text-red-400 text-xs mt-1">{errors.name}</p>}
                            </div>
                            <div>
                                <label className="block text-sm text-gray-400 mb-2">Email</label>
                                <input 
                                    type="email" 
                                    className="w-full bg-gray-900 border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 outline-none"
                                    value={data.email}
                                    onChange={e => setData('email', e.target.value)}
                                />
                                {errors.email && <p className="text-red-400 text-xs mt-1">{errors.email}</p>}
                            </div>
                            <div>
                                <label className="block text-sm text-gray-400 mb-2">Nova Senha (opcional)</label>
                                <input 
                                    type="password" 
                                    className="w-full bg-gray-900 border border-gray-700 rounded-xl p-3 focus:ring-2 focus:ring-blue-500 outline-none"
                                    value={data.password}
                                    onChange={e => setData('password', e.target.value)}
                                    placeholder="Deixe em branco para manter a atual"
                                />
                                {errors.password && <p className="text-red-400 text-xs mt-1">{errors.password}</p>}
                            </div>
                            <div className="flex gap-4 mt-8">
                                <button 
                                    type="submit" 
                                    disabled={processing}
                                    className="flex-1 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 py-3 rounded-xl font-bold transition-colors"
                                >
                                    {processing ? 'Salvando...' : 'Salvar Alterações'}
                                </button>
                                <button 
                                    type="button" 
                                    onClick={() => setEditingUser(null)}
                                    className="flex-1 bg-gray-700 hover:bg-gray-600 py-3 rounded-xl font-bold transition-colors"
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

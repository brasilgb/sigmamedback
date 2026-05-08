import React from 'react';
import { Head, useForm } from '@inertiajs/react';

const Login: React.FC = () => {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/login');
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-900 p-6 font-sans">
            <Head title="Admin Login - Meu Controle" />

            <div className="w-full max-w-md rounded-3xl border border-gray-700 bg-gray-800 p-10 shadow-2xl">
                <div className="mb-10 text-center">
                    <img
                        src="/images/logo_branco.png"
                        alt="Meu Controle"
                        className="mx-auto mb-6 h-16 w-auto"
                    />
                    <h1 className="bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-3xl font-extrabold text-transparent">
                        Meu Controle Root
                    </h1>
                    <p className="mt-2 text-gray-400">
                        Acesso restrito ao administrador
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div>
                        <label className="mb-2 block text-sm text-gray-400">
                            E-mail
                        </label>
                        <input
                            type="email"
                            className="w-full rounded-xl border border-gray-700 bg-gray-900 p-4 transition-all outline-none focus:ring-2 focus:ring-blue-500"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="admin@meucontrole.app"
                        />
                        {errors.email && (
                            <p className="mt-2 text-xs text-red-400">
                                {errors.email}
                            </p>
                        )}
                    </div>
                    <div>
                        <label className="mb-2 block text-sm text-gray-400">
                            Senha
                        </label>
                        <input
                            type="password"
                            className="w-full rounded-xl border border-gray-700 bg-gray-900 p-4 transition-all outline-none focus:ring-2 focus:ring-blue-500"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            placeholder="••••••••"
                        />
                        {errors.password && (
                            <p className="mt-2 text-xs text-red-400">
                                {errors.password}
                            </p>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full transform rounded-xl bg-gradient-to-r from-blue-600 to-emerald-600 py-4 font-bold text-white shadow-lg shadow-blue-900/20 transition-all hover:scale-[1.02] hover:from-blue-500 hover:to-emerald-500 disabled:opacity-50"
                    >
                        {processing ? 'Autenticando...' : 'Entrar no Painel'}
                    </button>
                </form>

                <div className="mt-8 text-center">
                    <p className="text-xs text-gray-500">
                        &copy; {new Date().getFullYear()} Meu Controle - Sistema
                        de Gestão Root
                    </p>
                </div>
            </div>
        </div>
    );
};

export default Login;

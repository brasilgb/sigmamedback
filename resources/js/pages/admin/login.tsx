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
        <div className="min-h-screen bg-gray-900 flex items-center justify-center p-6 font-sans">
            <Head title="Admin Login - SigmaMed" />
            
            <div className="w-full max-w-md bg-gray-800 border border-gray-700 rounded-3xl p-10 shadow-2xl">
                <div className="text-center mb-10">
                    <h1 className="text-3xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">
                        SigmaMed Root
                    </h1>
                    <p className="text-gray-400 mt-2">Acesso restrito ao administrador</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div>
                        <label className="block text-sm text-gray-400 mb-2">E-mail</label>
                        <input 
                            type="email" 
                            className="w-full bg-gray-900 border border-gray-700 rounded-xl p-4 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                            value={data.email}
                            onChange={e => setData('email', e.target.value)}
                            placeholder="admin@sigmamed.com"
                        />
                        {errors.email && <p className="text-red-400 text-xs mt-2">{errors.email}</p>}
                    </div>
                    <div>
                        <label className="block text-sm text-gray-400 mb-2">Senha</label>
                        <input 
                            type="password" 
                            className="w-full bg-gray-900 border border-gray-700 rounded-xl p-4 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                            value={data.password}
                            onChange={e => setData('password', e.target.value)}
                            placeholder="••••••••"
                        />
                        {errors.password && <p className="text-red-400 text-xs mt-2">{errors.password}</p>}
                    </div>

                    <button 
                        type="submit" 
                        disabled={processing}
                        className="w-full bg-gradient-to-r from-blue-600 to-emerald-600 hover:from-blue-500 hover:to-emerald-500 disabled:opacity-50 py-4 rounded-xl font-bold text-white shadow-lg shadow-blue-900/20 transition-all transform hover:scale-[1.02]"
                    >
                        {processing ? 'Autenticando...' : 'Entrar no Painel'}
                    </button>
                </form>
                
                <div className="mt-8 text-center">
                    <p className="text-gray-500 text-xs">
                        &copy; {new Date().getFullYear()} SigmaMed - Sistema de Gestão Root
                    </p>
                </div>
            </div>
        </div>
    );
};

export default Login;

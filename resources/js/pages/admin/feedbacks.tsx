import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface Feedback {
    id: number;
    rating: number | null;
    comment: string | null;
    source: string;
    app_version: string | null;
    platform: string | null;
    created_at: string;
    tenant: {
        id: number;
        name: string;
    };
    user: {
        id: number;
        name: string;
        email: string;
    };
}

interface Props {
    feedbacks: {
        data: Feedback[];
        links: any[];
    };
    stats: {
        total: number;
        average_rating: number;
        comments: number;
        latest_at: string | null;
    };
    ratingDistribution: Record<string, number>;
}

const Feedbacks: React.FC<Props> = ({ feedbacks, stats, ratingDistribution }) => {
    const maxRatingCount = Math.max(...Object.values(ratingDistribution), 1);

    const formatDate = (date: string | null) => {
        if (!date) {
            return 'Sem registros';
        }

        return new Date(date).toLocaleString('pt-BR');
    };

    const renderStars = (rating: number | null) => {
        if (!rating) {
            return <span className="text-sm text-gray-500">Sem nota</span>;
        }

        return (
            <span className="font-mono text-sm text-yellow-300">
                {'★'.repeat(rating)}
                <span className="text-gray-600">{'★'.repeat(5 - rating)}</span>
            </span>
        );
    };

    return (
        <div className="min-h-screen bg-gray-900 p-8 font-sans text-gray-100">
            <Head title="Feedbacks - SigmaMed" />

            <div className="mx-auto max-w-7xl">
                <header className="mb-10 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="bg-gradient-to-r from-yellow-300 to-emerald-300 bg-clip-text text-4xl font-extrabold text-transparent">
                            Feedbacks
                        </h1>
                        <p className="mt-2 text-gray-400">Notas e sugestões enviadas pelo app mobile</p>
                    </div>
                    <nav className="flex flex-wrap gap-3">
                        <Link href="/admin" className="rounded-lg bg-gray-800 px-4 py-2 transition-colors hover:bg-gray-700">
                            Dashboard
                        </Link>
                        <Link href="/admin/payments" className="rounded-lg bg-gray-800 px-4 py-2 transition-colors hover:bg-gray-700">
                            Pagamentos
                        </Link>
                    </nav>
                </header>

                <div className="mb-8 grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div className="rounded-lg border border-gray-700 bg-gray-800 p-5">
                        <p className="mb-1 text-sm text-gray-400">Total recebido</p>
                        <p className="text-3xl font-bold">{stats.total}</p>
                    </div>
                    <div className="rounded-lg border border-gray-700 bg-gray-800 p-5">
                        <p className="mb-1 text-sm text-gray-400">Nota média</p>
                        <p className="text-3xl font-bold text-yellow-300">{stats.average_rating || '—'}</p>
                    </div>
                    <div className="rounded-lg border border-gray-700 bg-gray-800 p-5">
                        <p className="mb-1 text-sm text-gray-400">Com comentário</p>
                        <p className="text-3xl font-bold text-emerald-300">{stats.comments}</p>
                    </div>
                    <div className="rounded-lg border border-gray-700 bg-gray-800 p-5">
                        <p className="mb-1 text-sm text-gray-400">Último envio</p>
                        <p className="text-sm font-semibold text-gray-200">{formatDate(stats.latest_at)}</p>
                    </div>
                </div>

                <div className="mb-8 rounded-lg border border-gray-700 bg-gray-800 p-6">
                    <h2 className="mb-5 text-lg font-bold">Distribuição por nota</h2>
                    <div className="grid gap-3">
                        {Object.entries(ratingDistribution).map(([rating, count]) => (
                            <div key={rating} className="grid grid-cols-[48px_1fr_40px] items-center gap-4">
                                <span className="font-mono text-sm text-yellow-300">{rating} ★</span>
                                <div className="h-3 overflow-hidden rounded bg-gray-900">
                                    <div
                                        className="h-full rounded bg-yellow-300"
                                        style={{ width: `${(count / maxRatingCount) * 100}%` }}
                                    />
                                </div>
                                <span className="text-right text-sm text-gray-400">{count}</span>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-gray-700 bg-gray-800 shadow-xl">
                    <div className="border-b border-gray-700 p-6">
                        <h2 className="text-xl font-bold">Feedbacks recentes</h2>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead>
                                <tr className="bg-gray-900/50 text-sm text-gray-400">
                                    <th className="px-6 py-4 font-medium">Usuário / Tenant</th>
                                    <th className="px-6 py-4 font-medium">Nota</th>
                                    <th className="px-6 py-4 font-medium">Comentário</th>
                                    <th className="px-6 py-4 font-medium">Origem</th>
                                    <th className="px-6 py-4 font-medium">App</th>
                                    <th className="px-6 py-4 font-medium">Data</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-700">
                                {feedbacks.data.map((feedback) => (
                                    <tr key={feedback.id} className="align-top transition-colors hover:bg-gray-700/30">
                                        <td className="px-6 py-4">
                                            <div className="font-medium">{feedback.user.name}</div>
                                            <div className="text-xs text-gray-500">{feedback.user.email}</div>
                                            <div className="mt-1 text-xs text-gray-400">{feedback.tenant.name}</div>
                                        </td>
                                        <td className="px-6 py-4">{renderStars(feedback.rating)}</td>
                                        <td className="max-w-xl px-6 py-4 text-sm leading-6 text-gray-300">
                                            {feedback.comment || <span className="text-gray-500">Sem comentário</span>}
                                        </td>
                                        <td className="px-6 py-4">
                                            <span className="rounded-md bg-gray-900 px-2 py-1 text-xs font-bold text-gray-300">
                                                {feedback.source}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-400">
                                            <div>{feedback.platform || '—'}</div>
                                            <div className="text-xs text-gray-500">{feedback.app_version || 'Sem versão'}</div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-400">{formatDate(feedback.created_at)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {feedbacks.data.length === 0 && (
                        <div className="p-10 text-center text-gray-400">
                            Nenhum feedback recebido ainda.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Feedbacks;

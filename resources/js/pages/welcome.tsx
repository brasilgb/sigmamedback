import { Head, Link } from '@inertiajs/react';
import { home, privacyPolicy } from '@/routes';

const features = [
    {
        title: 'Registros de saude em um so lugar',
        description:
            'Acompanhe glicose, pressao arterial, peso, medicamentos e historico de uso no mesmo fluxo.',
    },
    {
        title: 'Perfis para rotina pessoal ou familiar',
        description:
            'Organize informacoes de quem usa o app e mantenha o cuidado mais claro para cada perfil.',
    },
    {
        title: 'Sincronizacao quando precisar',
        description:
            'Tenha seus dados importantes preservados na conta e prontos para restauracao entre dispositivos.',
    },
];

const metrics = [
    ['Glicose', 'mg/dL'],
    ['Pressao', 'mmHg'],
    ['Peso', 'kg'],
    ['Remedios', 'horarios'],
];

export default function Welcome() {
    return (
        <>
            <Head title="Meu Controle">
                <meta
                    name="description"
                    content="Meu Controle ajuda voce a acompanhar indicadores de saude, medicamentos e perfis familiares com uma rotina simples e organizada."
                />
            </Head>

            <main className="min-h-screen bg-gray-950 font-sans text-white">
                <section className="border-b border-white/10 bg-[radial-gradient(circle_at_top_left,#2563eb33,transparent_32rem),radial-gradient(circle_at_bottom_right,#10b9812e,transparent_30rem)]">
                    <div className="mx-auto grid min-h-[92vh] w-full max-w-7xl grid-cols-1 items-center gap-12 px-6 py-8 sm:px-8 lg:grid-cols-[1fr_420px] lg:px-10">
                        <div className="flex flex-col gap-8">
                            <nav className="flex items-center justify-between gap-4 text-sm">
                                <Link
                                    href={home.url()}
                                    className="text-lg font-extrabold tracking-tight text-white"
                                >
                                    Meu Controle
                                </Link>
                                <Link
                                    href={privacyPolicy.url()}
                                    className="rounded-md border border-white/15 px-4 py-2 font-semibold text-gray-200 transition hover:border-emerald-300 hover:text-emerald-200"
                                >
                                    Privacidade
                                </Link>
                            </nav>

                            <div className="max-w-3xl">
                                <p className="mb-4 text-sm font-bold tracking-[0.22em] text-emerald-300 uppercase">
                                    Saude diaria sem complicar
                                </p>
                                <h1 className="text-5xl leading-[1.02] font-black tracking-tight text-white sm:text-6xl lg:text-7xl">
                                    Meu Controle
                                </h1>
                                <p className="mt-6 max-w-2xl text-lg leading-8 text-gray-300 sm:text-xl">
                                    Um app para registrar indicadores de saude,
                                    acompanhar medicamentos e manter a rotina de
                                    cuidado mais organizada para voce ou sua
                                    familia.
                                </p>
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                <a
                                    href="#recursos"
                                    className="rounded-md bg-emerald-400 px-5 py-3 text-center font-bold text-gray-950 shadow-lg shadow-emerald-950/30 transition hover:bg-emerald-300"
                                >
                                    Ver recursos
                                </a>
                                <Link
                                    href={privacyPolicy.url()}
                                    className="rounded-md border border-blue-300/40 px-5 py-3 text-center font-bold text-blue-100 transition hover:border-blue-200 hover:bg-blue-400/10"
                                >
                                    Politica de privacidade
                                </Link>
                            </div>
                        </div>

                        <div className="mx-auto w-full max-w-[360px]">
                            <div className="rounded-[2rem] border border-white/15 bg-gray-900 p-4 shadow-2xl shadow-blue-950/40">
                                <div className="rounded-[1.45rem] bg-gray-950 p-5">
                                    <div className="mb-6 flex items-center justify-between">
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.2em] text-blue-300 uppercase">
                                                Hoje
                                            </p>
                                            <h2 className="mt-1 text-2xl font-black text-white">
                                                Resumo
                                            </h2>
                                        </div>
                                        <span className="rounded-full bg-emerald-400 px-3 py-1 text-xs font-black text-gray-950">
                                            Sync
                                        </span>
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        {metrics.map(([label, unit], index) => (
                                            <div
                                                key={label}
                                                className="rounded-lg border border-white/10 bg-white/[0.04] p-4"
                                            >
                                                <p className="text-xs text-gray-400">
                                                    {label}
                                                </p>
                                                <p className="mt-3 text-2xl font-black text-white">
                                                    {index === 0
                                                        ? '98'
                                                        : index === 1
                                                          ? '12/8'
                                                          : index === 2
                                                            ? '72'
                                                            : '3'}
                                                </p>
                                                <p className="text-xs text-emerald-300">
                                                    {unit}
                                                </p>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="mt-5 rounded-lg border border-blue-400/20 bg-blue-400/10 p-4">
                                        <p className="text-sm font-bold text-blue-100">
                                            Proximo medicamento
                                        </p>
                                        <div className="mt-3 flex items-center justify-between gap-4">
                                            <span className="text-gray-300">
                                                20:00
                                            </span>
                                            <span className="rounded-md bg-blue-500 px-3 py-1 text-sm font-bold">
                                                Lembrar
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="recursos"
                    className="bg-gray-950 px-6 py-20 sm:px-8"
                >
                    <div className="mx-auto max-w-7xl">
                        <div className="max-w-2xl">
                            <p className="text-sm font-bold tracking-[0.22em] text-blue-300 uppercase">
                                Recursos
                            </p>
                            <h2 className="mt-3 text-3xl font-black text-white sm:text-4xl">
                                Informacao clara para acompanhar sua rotina.
                            </h2>
                        </div>

                        <div className="mt-10 grid grid-cols-1 gap-4 md:grid-cols-3">
                            {features.map((feature) => (
                                <article
                                    key={feature.title}
                                    className="rounded-lg border border-white/10 bg-white/[0.04] p-6"
                                >
                                    <h3 className="text-xl font-black text-white">
                                        {feature.title}
                                    </h3>
                                    <p className="mt-4 leading-7 text-gray-300">
                                        {feature.description}
                                    </p>
                                </article>
                            ))}
                        </div>
                    </div>
                </section>

                <footer className="border-t border-white/10 bg-gray-950 px-6 py-8 text-sm text-gray-400 sm:px-8">
                    <div className="mx-auto flex max-w-7xl flex-col justify-between gap-4 sm:flex-row">
                        <p>
                            Copyright {new Date().getFullYear()} Meu Controle.
                            Todos os direitos reservados.
                        </p>
                        <Link
                            href={privacyPolicy.url()}
                            className="font-semibold text-emerald-300 hover:text-emerald-200"
                        >
                            Politica de privacidade
                        </Link>
                    </div>
                </footer>
            </main>
        </>
    );
}

import { Head, Link } from '@inertiajs/react';
import { home } from '@/routes';

const sections = [
    {
        title: 'Dados que coletamos',
        items: [
            'Dados de conta, como nome, e-mail e senha protegida.',
            'Dados de perfil, como idade, altura, peso alvo, observacoes e foto quando enviada.',
            'Registros informados no app, como glicose, pressao arterial, peso, medicamentos e historico de uso.',
            'Informacoes tecnicas necessarias para login, seguranca, sincronizacao e funcionamento do servico.',
        ],
    },
    {
        title: 'Como usamos os dados',
        items: [
            'Para criar e autenticar sua conta.',
            'Para exibir, sincronizar e restaurar seus registros de saude no app.',
            'Para permitir recursos de assinatura, suporte, feedback e melhoria do servico.',
            'Para proteger contas, prevenir abuso e cumprir obrigacoes legais.',
        ],
    },
    {
        title: 'Compartilhamento',
        items: [
            'Nao vendemos dados pessoais.',
            'Podemos compartilhar dados somente com provedores necessarios para hospedagem, autenticacao, armazenamento, pagamentos e operacao do app.',
            'Tambem poderemos compartilhar informacoes quando exigido por lei ou para proteger direitos, seguranca e integridade do servico.',
        ],
    },
    {
        title: 'Controle e exclusao',
        items: [
            'Voce pode revisar e atualizar dados da conta e dos perfis pelo aplicativo.',
            'Voce pode solicitar ou executar a exclusao da conta. Quando a conta e excluida, os dados associados sao removidos conforme os prazos tecnicos e legais aplicaveis.',
            'Dados locais no dispositivo podem precisar ser apagados pelo proprio app ou pelas configuracoes do sistema operacional.',
        ],
    },
];

export default function PrivacyPolicy() {
    return (
        <>
            <Head title="Politica de Privacidade - Meu Controle">
                <meta
                    name="description"
                    content="Politica de privacidade publica do app Meu Controle."
                />
            </Head>

            <main className="min-h-screen bg-gray-950 font-sans text-white">
                <header className="border-b border-white/10 bg-gray-900/80 px-6 py-5 sm:px-8">
                    <div className="mx-auto flex max-w-5xl items-center justify-between gap-4">
                        <Link
                            href={home.url()}
                            className="text-lg font-extrabold tracking-tight text-white"
                        >
                            Meu Controle
                        </Link>
                        <Link
                            href={home.url()}
                            className="rounded-md border border-white/15 px-4 py-2 text-sm font-semibold text-gray-200 transition hover:border-emerald-300 hover:text-emerald-200"
                        >
                            Voltar
                        </Link>
                    </div>
                </header>

                <article className="mx-auto max-w-5xl px-6 py-14 sm:px-8">
                    <p className="text-sm font-bold tracking-[0.22em] text-emerald-300 uppercase">
                        Documento publico
                    </p>
                    <h1 className="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">
                        Politica de Privacidade
                    </h1>
                    <p className="mt-4 max-w-3xl text-lg leading-8 text-gray-300">
                        Esta politica explica como o app Meu Controle coleta,
                        usa, armazena e protege informacoes fornecidas por seus
                        usuarios. Ultima atualizacao: 8 de maio de 2026.
                    </p>

                    <div className="mt-10 rounded-lg border border-blue-400/20 bg-blue-400/10 p-6">
                        <h2 className="text-xl font-black text-blue-100">
                            Aviso sobre saude
                        </h2>
                        <p className="mt-3 leading-7 text-gray-300">
                            O Meu Controle ajuda no registro e organizacao de
                            informacoes. Ele nao substitui orientacao,
                            diagnostico, acompanhamento ou tratamento realizado
                            por profissionais de saude.
                        </p>
                    </div>

                    <div className="mt-10 grid grid-cols-1 gap-5">
                        {sections.map((section) => (
                            <section
                                key={section.title}
                                className="rounded-lg border border-white/10 bg-white/[0.04] p-6"
                            >
                                <h2 className="text-2xl font-black text-white">
                                    {section.title}
                                </h2>
                                <ul className="mt-5 flex flex-col gap-3 text-gray-300">
                                    {section.items.map((item) => (
                                        <li key={item} className="leading-7">
                                            {item}
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ))}
                    </div>

                    <section className="mt-10 rounded-lg border border-white/10 bg-white/[0.04] p-6">
                        <h2 className="text-2xl font-black text-white">
                            Seguranca, retencao e transferencias
                        </h2>
                        <p className="mt-5 leading-7 text-gray-300">
                            Adotamos medidas tecnicas e organizacionais para
                            proteger os dados. Nenhum sistema e totalmente imune
                            a riscos, mas buscamos limitar o acesso, preservar a
                            confidencialidade e manter os dados pelo tempo
                            necessario para fornecer o servico, cumprir
                            obrigacoes legais e resolver disputas.
                        </p>
                    </section>

                    <section className="mt-10 rounded-lg border border-emerald-400/20 bg-emerald-400/10 p-6">
                        <h2 className="text-2xl font-black text-emerald-100">
                            Contato
                        </h2>
                        <p className="mt-5 leading-7 text-gray-300">
                            Para duvidas sobre privacidade, acesso, correcao ou
                            exclusao de dados, entre em contato pelo e-mail{' '}
                            <a
                                href="mailto:contato@sigmaos.com.br"
                                className="font-bold text-emerald-300 hover:text-emerald-200"
                            >
                                contato@sigmaos.com.br
                            </a>
                            .
                        </p>
                    </section>
                </article>
            </main>
        </>
    );
}

import { Head, Link } from '@inertiajs/react';
import { home } from '@/routes';

const sections = [
    {
        title: 'Dados que coletamos',
        items: [
            'Dados de conta, como nome, e-mail e senha protegida.',
            'Dados de perfil, como idade, altura, peso alvo, observações e foto quando enviada.',
            'Registros informados no app, como glicose, pressão arterial, peso, medicamentos e histórico de uso.',
            'Informações técnicas necessárias para login, segurança, sincronização e funcionamento do serviço.',
        ],
    },
    {
        title: 'Como usamos os dados',
        items: [
            'Para criar e autenticar sua conta.',
            'Para exibir, sincronizar e restaurar seus registros de saúde no app.',
            'Para permitir recursos de assinatura, suporte, feedback e melhoria do serviço.',
            'Para proteger contas, prevenir abuso e cumprir obrigações legais.',
        ],
    },
    {
        title: 'Compartilhamento',
        items: [
            'Não vendemos dados pessoais.',
            'Podemos compartilhar dados somente com provedores necessários para hospedagem, autenticação, armazenamento, pagamentos e operação do app.',
            'Também poderemos compartilhar informações quando exigido por lei ou para proteger direitos, segurança e integridade do serviço.',
        ],
    },
    {
        title: 'Controle e exclusão',
        items: [
            'Você pode revisar e atualizar dados da conta e dos perfis pelo aplicativo.',
            'A exclusão da conta e dos dados pode ser feita pelo próprio usuário na tela de perfil do aplicativo ou solicitada pelo e-mail contato@sigmaos.com.br.',
            'Quando a conta é excluída, os dados associados são removidos conforme os prazos técnicos e legais aplicáveis.',
            'Dados locais no dispositivo podem precisar ser apagados pelo próprio app ou pelas configurações do sistema operacional.',
        ],
    },
];

export default function PrivacyPolicy() {
    return (
        <>
            <Head title="Política de Privacidade - Meu Controle">
                <meta
                    name="description"
                    content="Política de privacidade pública do app Meu Controle."
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
                        Documento público
                    </p>
                    <h1 className="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">
                        Política de Privacidade
                    </h1>
                    <p className="mt-4 max-w-3xl text-lg leading-8 text-gray-300">
                        Esta política explica como o app Meu Controle coleta,
                        usa, armazena e protege informações fornecidas por seus
                        usuários. Última atualização: 8 de maio de 2026.
                    </p>

                    <div className="mt-10 rounded-lg border border-blue-400/20 bg-blue-400/10 p-6">
                        <h2 className="text-xl font-black text-blue-100">
                            Aviso sobre saúde
                        </h2>
                        <p className="mt-3 leading-7 text-gray-300">
                            O Meu Controle ajuda no registro e organização de
                            informações. Ele não substitui orientação,
                            diagnóstico, acompanhamento ou tratamento realizado
                            por profissionais de saúde.
                        </p>
                    </div>

                    <div className="mt-10 grid grid-cols-1 gap-5">
                        {sections.map((section) => (
                            <section
                                key={section.title}
                                className={
                                    section.title === 'Controle e exclusão'
                                        ? 'rounded-lg border border-red-400/30 bg-red-500/10 p-6'
                                        : 'rounded-lg border border-white/10 bg-white/[0.04] p-6'
                                }
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
                            Segurança, retenção e transferências
                        </h2>
                        <p className="mt-5 leading-7 text-gray-300">
                            Adotamos medidas técnicas e organizacionais para
                            proteger os dados. Nenhum sistema é totalmente imune
                            a riscos, mas buscamos limitar o acesso, preservar a
                            confidencialidade e manter os dados pelo tempo
                            necessário para fornecer o serviço, cumprir
                            obrigações legais e resolver disputas.
                        </p>
                    </section>

                    <section className="mt-10 rounded-lg border border-emerald-400/20 bg-emerald-400/10 p-6">
                        <h2 className="text-2xl font-black text-emerald-100">
                            Contato
                        </h2>
                        <p className="mt-5 leading-7 text-gray-300">
                            Para dúvidas sobre privacidade, acesso, correção ou
                            exclusão de dados, entre em contato pelo e-mail{' '}
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

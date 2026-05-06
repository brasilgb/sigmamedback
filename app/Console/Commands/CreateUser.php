<?php

namespace App\Console\Commands;

use App\Services\UserAccountService;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria um novo usuário solicitando dados de forma interativa';

    /**
     * Execute the console command.
     */
    public function handle(UserAccountService $userAccountService): int
    {
        $name = text(
            label: 'Qual o nome do usuário?',
            placeholder: 'Ex: João Silva',
            required: true
        );

        $email = text(
            label: 'Qual o e-mail do usuário?',
            placeholder: 'exemplo@email.com',
            required: true,
            validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL) ? 'E-mail inválido.' : null
        );

        $password = password(
            label: 'Defina uma senha para o usuário',
            required: true,
            validate: fn (string $value) => strlen($value) < 8 ? 'A senha deve ter pelo menos 8 caracteres.' : null
        );

        $isAdmin = confirm(
            label: 'Este usuário será um administrador?',
            default: false
        );

        $accountUsage = $isAdmin ? 'personal' : select(
            label: 'Qual será o tipo de conta?',
            options: [
                'personal' => 'Pessoal',
                'family' => 'Familiar',
                'professional' => 'Profissional',
            ],
            default: 'personal'
        );

        $userAccountService->createOrUpdateConsoleUser(
            name: $name,
            email: $email,
            password: $password,
            isAdmin: $isAdmin,
            accountUsage: $accountUsage,
        );

        $this->info("\n✅ Usuário '{$name}' ({$email}) criado com sucesso!");
        if ($isAdmin) {
            $this->warn('⚠️  Atenção: Este usuário possui privilégios de administrador.');
        } else {
            $this->info('Conta, perfil e pagamento inicial foram criados ou conferidos.');
        }

        return self::SUCCESS;
    }
}

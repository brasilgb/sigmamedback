<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
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
    public function handle()
    {
        // Solicita o Nome
        $name = text(
            label: 'Qual o nome do usuário?',
            placeholder: 'Ex: João Silva',
            required: true
        );

        // Solicita o E-mail com validação
        $email = text(
            label: 'Qual o e-mail do usuário?',
            placeholder: 'exemplo@email.com',
            required: true,
            validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL) ? 'E-mail inválido.' : null
        );

        // Solicita a Senha
        $password = password(
            label: 'Defina uma senha para o usuário',
            required: true,
            validate: fn (string $value) => strlen($value) < 8 ? 'A senha deve ter pelo menos 8 caracteres.' : null
        );

        // Pergunta se deve ser administrador
        $isAdmin = confirm(
            label: 'Este usuário será um administrador?',
            default: false
        );

        // Cria ou atualiza o usuário
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => $isAdmin,
            ]
        );

        $this->info("\n✅ Usuário '{$name}' ({$email}) criado com sucesso!");
        if ($isAdmin) {
            $this->warn('⚠️  Atenção: Este usuário possui privilégios de administrador.');
        }

        return self::SUCCESS;
    }
}

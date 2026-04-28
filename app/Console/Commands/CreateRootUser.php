<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateRootUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:root {email?} {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update a root (admin) user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? text(
            label: 'Qual o email do usuário root?',
            placeholder: 'admin@sigmamed.com.br',
            required: true,
            validate: fn (string $value) => ! filter_var($value, FILTER_VALIDATE_EMAIL) ? 'Email inválido.' : null
        );

        $password = $this->argument('password') ?? password(
            label: 'Qual a senha do usuário root?',
            required: true,
            validate: fn (string $value) => strlen($value) < 8 ? 'A senha deve ter pelo menos 8 caracteres.' : null
        );

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Root Admin',
                'password' => Hash::make($password),
                'is_admin' => true,
            ]
        );

        $this->info("Usuário root {$email} criado/atualizado com sucesso.");

        return self::SUCCESS;
    }
}

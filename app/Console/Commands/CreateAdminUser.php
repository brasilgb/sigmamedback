<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'make:admin {email} {password}';

    protected $description = 'Create a new admin user';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin Root',
                'password' => Hash::make($password),
                'is_admin' => true,
            ]
        );

        $this->info("Usuário admin {$email} criado com sucesso.");
    }
}

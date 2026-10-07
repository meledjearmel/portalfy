<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-user {--name= : Nom de l\'utilisateur} {--email= : Adresse e-mail} {--password= : Mot de passe (demandé si absent)}')]
#[Description("Crée un utilisateur d'administration Portalfy (guard web).")]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $data = [
            'name' => $this->option('name') ?? text(label: 'Nom', required: true),
            'email' => $this->option('email') ?? text(label: 'E-mail', required: true),
            'password' => $this->option('password') ?? password(label: 'Mot de passe', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->forceCreate([
            ...$validator->validated(),
            'email_verified_at' => now(),
        ]);

        $this->components->info("Utilisateur {$user->email} créé.");

        return self::SUCCESS;
    }
}

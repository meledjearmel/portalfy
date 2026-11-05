<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\text;

#[Signature('app:create-user {--name= : Nom de l\'utilisateur} {--email= : Adresse e-mail} {--password= : Mot de passe (8 caractères min., généré si absent)}')]
#[Description("Crée un utilisateur d'administration Portalfy (guard web).")]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $generatedPassword = $this->option('password') === null ? Str::password(8) : null;

        $data = [
            'name' => $this->option('name') ?? text(label: 'Nom', required: true),
            'email' => $this->option('email') ?? text(label: 'E-mail', required: true),
            'password' => $this->option('password') ?? $generatedPassword,
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::min(8)],
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

        if ($generatedPassword !== null) {
            $this->components->twoColumnDetail('Mot de passe', $generatedPassword);
        }

        return self::SUCCESS;
    }
}

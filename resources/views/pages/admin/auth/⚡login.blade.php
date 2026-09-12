<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.auth')] #[Title('Connexion administrateur')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Authentification maison sur le guard "web" : Fortify (installé pour ce
     * projet) est dédié au guard "customer", pas à l'admin — cf. étape 8 et
     * config('fortify.guard'). Deux pages de connexion strictement séparées.
     */
    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($throttleKey).' secondes.',
            ]);
        }

        if (! Auth::guard('web')->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'Ces identifiants sont incorrects.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        $this->redirect(route('admin.dashboard'), navigate: true);
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col items-center gap-2 text-center">
        <flux:heading size="xl">Espace administrateur</flux:heading>
        <flux:text class="text-zinc-500">Réservé au personnel autorisé.</flux:text>
    </div>

    <form wire:submit="login" class="flex flex-col gap-6">
        <flux:field>
            <flux:label>Adresse email</flux:label>
            <flux:input wire:model="email" type="email" required autofocus autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>Mot de passe</flux:label>
            <flux:input wire:model="password" type="password" required autocomplete="current-password" viewable />
            <flux:error name="password" />
        </flux:field>

        <flux:checkbox wire:model="remember" label="Se souvenir de moi" />

        <flux:button type="submit" variant="primary" class="w-full">
            Se connecter
        </flux:button>
    </form>
</div>

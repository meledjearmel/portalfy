<x-layouts::public :title="__('Mot de passe oublié')">
    <div class="mx-auto flex w-full max-w-md flex-col gap-8">
        <div class="flex flex-col items-center gap-2 text-center">
            <flux:badge size="sm" class="glass-button">Mon compte</flux:badge>
            <flux:heading size="xl" class="glass-text text-3xl font-extrabold">Mot de passe oublié</flux:heading>
            <flux:text class="glass-text opacity-90">
                Recevez un lien de réinitialisation par email.
            </flux:text>
        </div>

        <x-auth-session-status class="glass-text text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="glass-card flex flex-col gap-6 p-8">
            @csrf

            <flux:field>
                <flux:label class="glass-text">Adresse email</flux:label>
                <flux:input
                    name="email"
                    type="email"
                    required
                    autofocus
                    placeholder="vous@exemple.com"
                />
                <flux:error name="email" />
            </flux:field>

            <flux:button variant="ghost" type="submit" class="glass-button w-full" data-test="email-password-reset-link-button">
                Envoyer le lien de réinitialisation
            </flux:button>
        </form>

        <div class="glass-text text-center text-sm opacity-90">
            <span>Ou revenez à la</span>
            <flux:link :href="route('login')" wire:navigate class="glass-text underline">connexion</flux:link>
        </div>
    </div>
</x-layouts::public>

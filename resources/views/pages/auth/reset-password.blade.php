<x-layouts::public :title="__('Réinitialiser le mot de passe')">
    <div class="mx-auto flex w-full max-w-md flex-col gap-8">
        <div class="flex flex-col items-center gap-2 text-center">
            <flux:badge size="sm" class="glass-button">Mon compte</flux:badge>
            <flux:heading size="xl" class="glass-text text-3xl font-extrabold">Réinitialiser le mot de passe</flux:heading>
            <flux:text class="glass-text opacity-90">
                Choisissez un nouveau mot de passe.
            </flux:text>
        </div>

        <x-auth-session-status class="glass-text text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="glass-card flex flex-col gap-6 p-8">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <flux:field>
                <flux:label class="glass-text">Adresse email</flux:label>
                <flux:input
                    name="email"
                    value="{{ request('email') }}"
                    type="email"
                    required
                    autocomplete="email"
                />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label class="glass-text">Nouveau mot de passe</flux:label>
                <flux:input
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label class="glass-text">Confirmer le mot de passe</flux:label>
                <flux:input
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                />
                <flux:error name="password_confirmation" />
            </flux:field>

            <flux:button variant="ghost" type="submit" class="glass-button w-full" data-test="reset-password-button">
                Réinitialiser le mot de passe
            </flux:button>
        </form>
    </div>
</x-layouts::public>

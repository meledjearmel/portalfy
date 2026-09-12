<x-layouts::public :title="__('Créer un compte')">
    <div class="mx-auto flex w-full max-w-md flex-col gap-8">
        <div class="flex flex-col items-center gap-2 text-center">
            <flux:badge size="sm" class="glass-button">Mon compte</flux:badge>
            <flux:heading size="xl" class="glass-text text-3xl font-extrabold">Créer un compte</flux:heading>
            <flux:text class="glass-text opacity-90">
                Retrouvez votre historique d'achats et vos factures.
            </flux:text>
        </div>

        <x-auth-session-status class="glass-text text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="glass-card flex flex-col gap-6 p-8">
            @csrf

            <flux:field>
                <flux:label class="glass-text">Adresse email</flux:label>
                <flux:input
                    name="email"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="vous@exemple.com"
                />
                <flux:error name="email" />
            </flux:field>

            <flux:field class="items-center text-center">
                <flux:label class="glass-text text-center">Numéro de téléphone</flux:label>
                <flux:input
                    name="phone"
                    :value="old('phone')"
                    type="tel"
                    inputmode="numeric"
                    required
                    autocomplete="tel"
                    placeholder="07 00 00 00 00"
                    x-mask="'99 99 99 99 99'"
                    class="text-center"
                />
                <flux:error name="phone" />
            </flux:field>

            <flux:field>
                <flux:label class="glass-text">Mot de passe</flux:label>
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

            <flux:button variant="ghost" type="submit" class="glass-button w-full" data-test="register-user-button">
                Créer mon compte
            </flux:button>
        </form>

        <div class="glass-text text-center text-sm opacity-90">
            <span>Déjà un compte ?</span>
            <flux:link :href="route('login')" wire:navigate class="glass-text underline">Se connecter</flux:link>
        </div>
    </div>
</x-layouts::public>

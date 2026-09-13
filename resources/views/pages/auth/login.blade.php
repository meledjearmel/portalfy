<x-layouts::public :title="__('Connexion')">
    <div class="mx-auto flex w-full max-w-md flex-col gap-8">
        <div class="flex flex-col items-center gap-2 text-center">
            <flux:badge size="sm" class="glass-button">Mon compte</flux:badge>
            <flux:heading size="xl" class="glass-text text-3xl font-extrabold">Connexion</flux:heading>
            <flux:text class="glass-text opacity-90">
                Retrouvez votre historique d'achats et vos factures.
            </flux:text>
        </div>

        <x-auth-session-status class="glass-text text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="glass-card flex flex-col gap-6 p-8">
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

            <flux:field>
                <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                    <flux:label class="glass-text">Mot de passe</flux:label>

                    @if (Route::has('password.request'))
                        <flux:link class="glass-text text-sm opacity-80" :href="route('password.request')" wire:navigate>
                            Mot de passe oublié ?
                        </flux:link>
                    @endif
                </div>

                <flux:input
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    viewable
                />
                <flux:error name="password" />
            </flux:field>

            <flux:label class="glass-text flex items-center gap-2">
                <flux:checkbox name="remember" :checked="old('remember')"/>
                Se souvenir de moi
            </flux:label>
            

            <flux:button variant="ghost" type="submit" class="glass-button w-full" data-test="login-button">
                Se connecter
            </flux:button>
        </form>

        <div class="glass-text text-center text-sm opacity-90">
            <span>Pas encore de compte ?</span>
            <flux:link :href="route('register')" wire:navigate class="glass-text underline">Créer un compte</flux:link>
        </div>
    </div>
</x-layouts::public>

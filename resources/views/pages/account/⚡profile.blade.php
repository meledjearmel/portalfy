<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Mon profil')] class extends Component
{
    //
};
?>

<div class="mx-auto flex w-full max-w-md flex-col gap-8">
    <div class="flex flex-col items-center gap-2 text-center">
        <flux:badge size="sm" class="glass-button">Mon compte</flux:badge>
        <flux:heading size="xl" class="glass-text text-3xl font-extrabold">Mon profil</flux:heading>
    </div>

    <div class="glass-card flex flex-col gap-6 p-8">
        <div class="flex flex-col gap-1">
            <flux:text class="glass-text text-sm opacity-70">Adresse email</flux:text>
            <flux:text class="glass-text font-medium">{{ auth('customer')->user()->email }}</flux:text>
        </div>

        <div class="flex flex-col gap-1">
            <flux:text class="glass-text text-sm opacity-70">Téléphone</flux:text>
            <flux:text class="glass-text font-medium">{{ auth('customer')->user()->phone }}</flux:text>
        </div>
    </div>

    <div class="flex flex-col gap-3">
        <flux:button :href="route('account.orders')" wire:navigate variant="ghost" class="glass-button w-full" icon="clock">
            Historique des achats
        </flux:button>

        <flux:button :href="route('account.invoices')" wire:navigate variant="ghost" class="glass-button w-full" icon="document-text">
            Mes factures
        </flux:button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" class="glass-button w-full" icon="arrow-right-start-on-rectangle">
                Se déconnecter
            </flux:button>
        </form>
    </div>
</div>

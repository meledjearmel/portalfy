<?php

use App\Models\HotspotAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title("Détail de l'accès")] class extends Component
{
    public HotspotAccount $hotspotAccount;

    public function mount(HotspotAccount $hotspotAccount): void
    {
        abort_unless(
            $hotspotAccount->order->customer_id === Auth::guard('customer')->id(),
            403,
        );

        $this->hotspotAccount = $hotspotAccount;
    }
};
?>

<div class="mx-auto flex w-full max-w-md flex-col gap-8">
    <div class="flex flex-col items-center gap-2 text-center">
        <flux:badge size="sm" class="glass-button">Accès actif</flux:badge>
        <flux:heading size="xl" class="glass-text text-3xl font-extrabold">
            {{ $hotspotAccount->order->package->name }}
        </flux:heading>
    </div>

    <div class="glass-card flex flex-col items-center gap-3 p-8 text-center">
        <flux:text class="glass-text text-sm opacity-80">Votre code</flux:text>
        <flux:heading size="xl" class="glass-text text-3xl font-extrabold tracking-widest">
            {{ $hotspotAccount->code }}
        </flux:heading>

        @if ($hotspotAccount->secret)
            <flux:text class="glass-text text-sm opacity-80">Mot de passe</flux:text>
            <flux:heading size="lg" class="glass-text text-xl font-bold tracking-widest">
                {{ $hotspotAccount->secret }}
            </flux:heading>
        @endif

        @if ($hotspotAccount->expires_at)
            <flux:text class="glass-text text-sm opacity-80">
                Valide jusqu'au {{ $hotspotAccount->expires_at->translatedFormat('d M Y à H:i') }}
            </flux:text>
        @endif

        <div
            x-data="{ copied: false }"
            class="flex w-full gap-3"
        >
            <flux:button
                variant="ghost"
                class="glass-button flex-1"
                x-on:click="
                    navigator.clipboard.writeText('{{ $hotspotAccount->code }}');
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                "
            >
                <span x-show="!copied">Copier le code</span>
                <span x-show="copied" x-cloak>Copié !</span>
            </flux:button>
        </div>
    </div>

    <flux:button :href="route('account.orders')" wire:navigate variant="ghost" class="glass-button w-full">
        Retour à l'historique
    </flux:button>
</div>

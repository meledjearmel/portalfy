<?php

use App\Actions\CaptureHotspotContextAction;
use App\Actions\ResolveHotspotLoginCredentialsAction;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\WifiZoneSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Vérification du paiement')] class extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order;
    }

    /**
     * Canal public (voir HotspotAccountProvisioned::broadcastOn()) : la
     * référence de la commande fait office de secret, pas besoin
     * d'authentification pour ce parcours anonyme.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            "echo:orders.{$this->order->reference},HotspotAccountProvisioned" => 'refreshStatus',
        ];
    }

    public function refreshStatus(): void
    {
        $this->order->refresh();
    }

    /**
     * Le contexte hotspot (voir CaptureHotspotContextAction) est ce qui
     * permet l'auto-connexion : sans lui (visiteur arrivé hors wifi
     * RapidNet), impossible de savoir où poster les identifiants, donc pas
     * de tentative silencieuse — l'écran l'explique à la place.
     */
    public function with(
        CaptureHotspotContextAction $hotspotContextAction,
        ResolveHotspotLoginCredentialsAction $resolveHotspotLoginCredentials,
    ): array {
        $zone = WifiZoneSetting::current();

        return [
            'zone' => $zone,
            'hotspotContext' => $hotspotContextAction->current(),
            'loginCredentials' => $this->order->hotspotAccount
                ? $resolveHotspotLoginCredentials->handle($this->order->hotspotAccount, $zone)
                : null,
        ];
    }
};
?>

<div
    class="mx-auto flex w-full max-w-md flex-col items-center gap-6 text-center"
    @if ($order->status === OrderStatus::Pending) wire:poll.5s="refreshStatus" @endif
>
    @if ($order->status === OrderStatus::Pending)
        <div class="glass-card flex w-full flex-col items-center gap-4 p-8">
            <flux:icon name="arrow-path" class="glass-text size-10 animate-spin" />
            <flux:heading size="xl" class="glass-text text-2xl font-extrabold">
                Vérification de votre paiement...
            </flux:heading>
            <flux:text class="glass-text opacity-90">
                Cela ne prend généralement que quelques secondes.
            </flux:text>
        </div>
    @elseif ($order->status === OrderStatus::Paid)
        <div
            class="glass-card flex w-full flex-col items-center gap-4 p-8"
            @if (! $order->hotspotAccount) wire:poll.5s="refreshStatus" @endif
        >
            <flux:icon name="check-circle" class="glass-text size-10" />
            <flux:heading size="xl" class="glass-text text-2xl font-extrabold">Paiement réussi</flux:heading>

            @if ($order->hotspotAccount)
                <flux:text class="glass-text text-sm opacity-80">Votre code d'accès</flux:text>
                <flux:heading size="xl" class="glass-text text-3xl font-extrabold tracking-widest">
                    {{ $order->hotspotAccount->code }}
                </flux:heading>

                @if ($order->hotspotAccount->secret)
                    <flux:text class="glass-text text-sm opacity-80">Mot de passe</flux:text>
                    <flux:heading size="lg" class="glass-text text-xl font-bold tracking-widest">
                        {{ $order->hotspotAccount->secret }}
                    </flux:heading>
                @endif

                {{-- wire:ignore sur tout le bloc : le formulaire d'auto-connexion ne doit
                être (re)créé qu'une seule fois, jamais retouché par un morph Livewire
                ultérieur (wire:poll, événement Reverb) qui redéclencherait sa
                soumission et re-tenterait l'authentification RouterOS. --}}
                <div wire:ignore class="flex w-full flex-col items-center gap-3">
                    <x-hotspot-login-actions
                        :code="$order->hotspotAccount->code"
                        :hotspot-context="$hotspotContext"
                        :login-credentials="$loginCredentials"
                        :share-url="route('hotspot-access.show', ['code' => $order->hotspotAccount->code])"
                        :zone-name="$zone->name"
                    />
                </div>
            @else
                <flux:text class="glass-text opacity-90">
                    Votre accès est en cours d'activation, votre code apparaîtra ici
                    dans quelques instants.
                </flux:text>
            @endif
        </div>
    @else
        <div class="glass-card flex w-full flex-col items-center gap-4 p-8">
            <flux:icon name="x-circle" class="glass-text size-10" />
            <flux:heading size="xl" class="glass-text text-2xl font-extrabold">Le paiement a échoué</flux:heading>
            <flux:text class="glass-text opacity-90">
                Aucun montant n'a été débité. Vous pouvez réessayer.
            </flux:text>

            <flux:button :href="route('orders.create', ['package' => $order->package])" wire:navigate variant="ghost" class="glass-button w-full">
                Réessayer
            </flux:button>
        </div>
    @endif
</div>

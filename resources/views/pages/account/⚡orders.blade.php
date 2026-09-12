<?php

use App\Enums\HotspotAccountStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.public')] #[Title('Historique des achats')] class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return [
            'orders' => Auth::guard('customer')->user()
                ->orders()
                ->with(['package', 'hotspotAccount', 'invoice'])
                ->latest()
                ->paginate(10),
        ];
    }

    /**
     * Le statut affiché au client se distingue du statut brut de la
     * commande : un paiement réussi peut être "actif" ou "expiré" selon
     * l'état réel du compte Hotspot provisionné.
     */
    public function accessLabel(Order $order): string
    {
        if ($order->status !== OrderStatus::Paid) {
            return match ($order->status) {
                OrderStatus::Pending => 'En attente de paiement',
                OrderStatus::Failed => 'Paiement échoué',
                OrderStatus::Refunded => 'Remboursée',
                OrderStatus::Expired => 'Expirée',
                default => '—',
            };
        }

        if (! $order->hotspotAccount) {
            return "Activation en cours";
        }

        $isActive = $order->hotspotAccount->status === HotspotAccountStatus::Active
            && (! $order->hotspotAccount->expires_at || $order->hotspotAccount->expires_at->isFuture());

        return $isActive ? 'Actif' : 'Expiré';
    }
};
?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-8">
    <div class="flex flex-col items-center gap-2 text-center">
        <flux:badge size="sm" class="glass-button">Mon compte</flux:badge>
        <flux:heading size="xl" class="glass-text text-3xl font-extrabold">Historique des achats</flux:heading>
    </div>

    @if ($orders->isEmpty())
        <div class="glass-card flex flex-col items-center gap-3 p-8 text-center">
            <flux:text class="glass-text opacity-90">Vous n'avez encore effectué aucun achat.</flux:text>
            <flux:button :href="route('packages.index')" wire:navigate variant="ghost" class="glass-button">
                Voir les forfaits
            </flux:button>
        </div>
    @else
        <div class="flex flex-col gap-4">
            @foreach ($orders as $order)
                <div class="glass-card flex items-center justify-between gap-4 p-6" wire:key="order-{{ $order->id }}">
                    <div class="flex flex-col gap-1">
                        <flux:text class="glass-text font-medium">{{ $order->package->name }}</flux:text>
                        <flux:text class="glass-text text-sm opacity-70">
                            {{ $order->created_at->translatedFormat('d M Y à H:i') }} · {{ number_format($order->amount, 0, ',', ' ') }} F
                        </flux:text>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <flux:badge size="sm" class="glass-button">{{ $this->accessLabel($order) }}</flux:badge>

                        @if ($order->status === OrderStatus::Paid && $order->hotspotAccount && $this->accessLabel($order) === 'Actif')
                            <flux:link :href="route('account.access.show', $order->hotspotAccount)" wire:navigate class="glass-text text-sm underline">
                                Voir l'accès
                            </flux:link>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{ $orders->links() }}
    @endif
</div>

<?php

use App\Enums\OrderStatus;
use App\Models\Order;
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

    public function refreshStatus(): void
    {
        $this->order->refresh();
    }
};
?>

<div
    class="mx-auto flex w-full max-w-md flex-col items-center gap-6 text-center"
    @if ($order->status === OrderStatus::Pending) wire:poll.3s="refreshStatus" @endif
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
        <div class="glass-card flex w-full flex-col items-center gap-4 p-8">
            <flux:icon name="check-circle" class="glass-text size-10" />
            <flux:heading size="xl" class="glass-text text-2xl font-extrabold">Paiement réussi</flux:heading>

            @if ($order->hotspotAccount)
                <flux:text class="glass-text text-sm opacity-80">Votre code d'accès</flux:text>
                <flux:heading size="xl" class="glass-text text-3xl font-extrabold tracking-widest">
                    {{ $order->hotspotAccount->code }}
                </flux:heading>

                <flux:button variant="ghost" class="glass-button mt-2 w-full" disabled>
                    Se connecter au WiFi
                </flux:button>
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

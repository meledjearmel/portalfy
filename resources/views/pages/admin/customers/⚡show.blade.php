<?php

use App\Models\Customer;
use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Client')] class extends Component
{
    use WithPagination;

    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function with(): array
    {
        return [
            'orders' => Order::query()
                ->with(['package', 'hotspotAccount'])
                ->where('customer_id', $this->customer->id)
                ->latest()
                ->paginate(15),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">{{ $customer->email }}</flux:heading>
            <flux:text class="text-[var(--muted-fg)]">{{ $customer->phone }}</flux:text>
        </div>

        <flux:button variant="ghost" :href="route('admin.customers.index')" wire:navigate>
            Retour aux clients
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="flex flex-col gap-1 console-card p-6">
            <flux:text class="text-sm text-[var(--muted-fg)]">Statut</flux:text>
            <flux:badge :color="$customer->status->color()" size="sm" class="w-fit">
                {{ $customer->status->label() }}
            </flux:badge>
        </div>

        <div class="flex flex-col gap-1 console-card p-6">
            <flux:text class="text-sm text-[var(--muted-fg)]">Inscrit le</flux:text>
            <flux:heading size="lg">{{ $customer->created_at->translatedFormat('d M Y') }}</flux:heading>
        </div>

        <div class="flex flex-col gap-1 console-card p-6">
            <flux:text class="text-sm text-[var(--muted-fg)]">Commandes</flux:text>
            <flux:heading size="lg">{{ $orders->total() }}</flux:heading>
        </div>
    </div>

    <div class="flex flex-col gap-4 console-card p-6">
        <flux:heading size="lg">Commandes</flux:heading>

        @if ($orders->isEmpty())
            <flux:text class="text-[var(--muted-fg)]">Ce client n'a passé aucune commande.</flux:text>
        @else
            <div class="console-table">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Forfait</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Code d'accès</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr wire:key="order-{{ $order->id }}">
                                <td class="px-4 py-3 font-mono">{{ $order->reference }}</td>
                                <td class="px-4 py-3">{{ $order->package->name }}</td>
                                <td class="px-4 py-3">{{ number_format($order->amount, 0, ',', ' ') }} F</td>
                                <td class="px-4 py-3">
                                    <flux:badge :color="$order->status->color()" size="sm">
                                        {{ $order->status->label() }}
                                    </flux:badge>
                                </td>
                                <td class="px-4 py-3 font-mono text-[var(--muted-fg)]">{{ $order->hotspotAccount?->code ?? '—' }}</td>
                                <td class="px-4 py-3 text-[var(--muted-fg)]">{{ $order->created_at->translatedFormat('d M Y à H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $orders->links() }}
        @endif
    </div>
</div>

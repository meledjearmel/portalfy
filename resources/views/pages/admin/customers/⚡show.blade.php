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
            <flux:text class="text-zinc-500">{{ $customer->phone }}</flux:text>
        </div>

        <flux:button variant="ghost" :href="route('admin.customers.index')" wire:navigate>
            Retour aux clients
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500">Statut</flux:text>
            <flux:badge :color="$customer->status->color()" size="sm" class="w-fit">
                {{ $customer->status->label() }}
            </flux:badge>
        </div>

        <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500">Inscrit le</flux:text>
            <flux:heading size="lg">{{ $customer->created_at->translatedFormat('d M Y') }}</flux:heading>
        </div>

        <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500">Commandes</flux:text>
            <flux:heading size="lg">{{ $orders->total() }}</flux:heading>
        </div>
    </div>

    <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
        <flux:heading size="lg">Commandes</flux:heading>

        @if ($orders->isEmpty())
            <flux:text class="text-zinc-500">Ce client n'a passé aucune commande.</flux:text>
        @else
            <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium text-zinc-500">Référence</th>
                            <th class="px-4 py-3 text-start font-medium text-zinc-500">Forfait</th>
                            <th class="px-4 py-3 text-start font-medium text-zinc-500">Montant</th>
                            <th class="px-4 py-3 text-start font-medium text-zinc-500">Statut</th>
                            <th class="px-4 py-3 text-start font-medium text-zinc-500">Code d'accès</th>
                            <th class="px-4 py-3 text-start font-medium text-zinc-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
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
                                <td class="px-4 py-3 font-mono text-zinc-500">{{ $order->hotspotAccount?->code ?? '—' }}</td>
                                <td class="px-4 py-3 text-zinc-500">{{ $order->created_at->translatedFormat('d M Y à H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $orders->links() }}
        @endif
    </div>
</div>

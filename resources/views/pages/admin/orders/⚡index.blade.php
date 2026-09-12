<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Commandes')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'orders' => Order::query()
                ->with(['package', 'customer'])
                ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                ->latest()
                ->paginate(15),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Commandes</flux:heading>

        <flux:select wire:model.live="status" class="max-w-xs">
            <flux:select.option value="">Tous les statuts</flux:select.option>
            @foreach (OrderStatus::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($orders->isEmpty())
        <flux:text class="text-zinc-500">Aucune commande ne correspond à ce filtre.</flux:text>
    @else
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-start dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Référence</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Forfait</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Client</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Montant</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Statut</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="px-4 py-3 font-mono">{{ $order->reference }}</td>
                            <td class="px-4 py-3">{{ $order->package->name }}</td>
                            <td class="px-4 py-3">{{ $order->customer?->email ?? $order->phone }}</td>
                            <td class="px-4 py-3">{{ number_format($order->amount, 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$order->status->color()" size="sm">
                                    {{ $order->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $order->created_at->translatedFormat('d M Y à H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    @endif
</div>

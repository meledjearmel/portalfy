<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title("Paiements en ligne")] class extends Component
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
        <x-admin.page-header title="Paiements en ligne" description="Chaque achat payé (ou tenté) sur le portail par Mobile Money ou carte." />

        <flux:select wire:model.live="status" class="max-w-xs">
            <flux:select.option value="">Tous les statuts</flux:select.option>
            @foreach (OrderStatus::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($orders->isEmpty())
        <flux:text class="text-[var(--muted-fg)]">Aucune commande ne correspond à ce filtre.</flux:text>
    @else
        <div class="console-table">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Forfait</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
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
                            <td class="px-4 py-3 text-[var(--muted-fg)]">{{ $order->created_at->translatedFormat('d M Y à H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    @endif
</div>

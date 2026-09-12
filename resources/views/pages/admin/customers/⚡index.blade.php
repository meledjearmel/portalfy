<?php

use App\Models\Customer;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Clients')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'customers' => Customer::query()
                ->withCount('orders')
                ->when($this->search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                    ->where('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")))
                ->latest()
                ->paginate(15),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Clients</flux:heading>

        <flux:input wire:model.live.debounce.300ms="search" placeholder="Rechercher par email ou téléphone..." class="max-w-xs" icon="magnifying-glass" />
    </div>

    @if ($customers->isEmpty())
        <flux:text class="text-zinc-500">Aucun client ne correspond à cette recherche.</flux:text>
    @else
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Email</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Téléphone</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Statut</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Commandes</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Inscrit le</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}">
                            <td class="px-4 py-3">{{ $customer->email }}</td>
                            <td class="px-4 py-3">{{ $customer->phone }}</td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$customer->status->color()" size="sm">
                                    {{ $customer->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">{{ $customer->orders_count }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $customer->created_at->translatedFormat('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $customers->links() }}
    @endif
</div>

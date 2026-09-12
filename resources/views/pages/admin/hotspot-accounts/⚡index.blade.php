<?php

use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Comptes Hotspot')] class extends Component
{
    use WithPagination;

    public function suspend(HotspotAccount $account): void
    {
        $account->update(['status' => HotspotAccountStatus::Suspended]);

        Flux::toast(variant: 'success', text: 'Accès suspendu.');
    }

    public function reactivate(HotspotAccount $account): void
    {
        $account->update(['status' => HotspotAccountStatus::Active]);

        Flux::toast(variant: 'success', text: 'Accès réactivé.');
    }

    /**
     * Prolonge l'accès de la durée initiale du forfait, à partir de la date
     * d'expiration actuelle si elle n'est pas encore passée, sinon à partir
     * de maintenant.
     */
    public function extend(HotspotAccount $account): void
    {
        $account->loadMissing('order.package');

        $base = $account->expires_at?->isFuture() ? $account->expires_at : now();

        $account->update([
            'expires_at' => $base->addMinutes($account->order->package->duration_minutes),
        ]);

        Flux::toast(variant: 'success', text: 'Accès prolongé.');
    }

    public function with(): array
    {
        return [
            'accounts' => HotspotAccount::query()
                ->with(['order.package', 'order.customer'])
                ->latest()
                ->paginate(15),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <flux:heading size="xl">Comptes Hotspot</flux:heading>

    @if ($accounts->isEmpty())
        <flux:text class="text-zinc-500">Aucun compte Hotspot pour le moment.</flux:text>
    @else
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Code</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Forfait</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Client</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Statut</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Expire le</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($accounts as $account)
                        <tr wire:key="account-{{ $account->id }}">
                            <td class="px-4 py-3 font-mono">{{ $account->code }}</td>
                            <td class="px-4 py-3">{{ $account->order->package->name }}</td>
                            <td class="px-4 py-3">{{ $account->order->customer?->email ?? $account->order->phone }}</td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$account->status->color()" size="sm">
                                    {{ $account->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">
                                {{ $account->expires_at?->translatedFormat('d M Y à H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="extend({{ $account->id }})">
                                        Prolonger
                                    </flux:button>

                                    @if ($account->status === HotspotAccountStatus::Suspended)
                                        <flux:button size="sm" variant="ghost" wire:click="reactivate({{ $account->id }})">
                                            Réactiver
                                        </flux:button>
                                    @else
                                        <flux:button size="sm" variant="danger" wire:click="suspend({{ $account->id }})">
                                            Suspendre
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $accounts->links() }}
    @endif
</div>

<?php

use App\Models\HotspotAccount;
use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Tableau de bord')] class extends Component
{
    /**
     * Rafraîchi en temps réel via AdminDashboardActivity (dispatché par
     * MarkOrderAsPaid/MarkOrderAsFailed) : pas de wire:poll nécessaire ici,
     * contrairement au parcours client anonyme (voir orders.return).
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            'echo-private:admin.dashboard,AdminDashboardActivity' => '$refresh',
        ];
    }

    public function with(): array
    {
        return [
            'revenue' => Order::query()->paid()->sum('amount'),
            'salesCount' => Order::query()->paid()->count(),
            'activeAccountsCount' => HotspotAccount::query()
                ->active()
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->count(),
            'recentOrders' => Order::query()
                ->with(['package', 'customer'])
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <flux:heading size="xl">Tableau de bord</flux:heading>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500">Chiffre d'affaires</flux:text>
            <flux:heading size="lg">{{ number_format($revenue, 0, ',', ' ') }} F</flux:heading>
        </div>

        <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500">Ventes</flux:text>
            <flux:heading size="lg">{{ $salesCount }}</flux:heading>
        </div>

        <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500">Comptes actifs</flux:text>
            <flux:heading size="lg">{{ $activeAccountsCount }}</flux:heading>
        </div>
    </div>

    <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
        <flux:heading size="lg">Activité récente</flux:heading>

        @if ($recentOrders->isEmpty())
            <flux:text class="text-zinc-500">Aucune commande pour le moment.</flux:text>
        @else
            <div class="flex flex-col divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($recentOrders as $order)
                    <div class="flex items-center justify-between gap-4 py-3" wire:key="recent-order-{{ $order->id }}">
                        <div class="flex flex-col gap-0.5">
                            <flux:text class="font-medium">{{ $order->package->name }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">
                                {{ $order->customer?->email ?? $order->phone }} ·
                                {{ $order->created_at->translatedFormat('d M Y à H:i') }}
                            </flux:text>
                        </div>

                        <flux:badge :color="$order->status->color()" size="sm">
                            {{ $order->status->label() }}
                        </flux:badge>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

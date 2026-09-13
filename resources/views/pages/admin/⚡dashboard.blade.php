<?php

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\Package;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;

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
            'activeCustomersCount' => Customer::query()->active()->count(),
            'activeVouchersCount' => HotspotAccount::query()
                ->active()
                ->whereNull('order_id')
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->count(),
            'activeSessionsCount' => $this->activeSessionsCount(),
            'dailyRevenue' => $this->dailyRevenue(),
            'ordersByStatus' => $this->ordersByStatus(),
            'topPackages' => $this->topPackages(),
            'recentOrders' => Order::query()
                ->with(['package', 'customer'])
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Null si le routeur est injoignable (l'écran l'affiche comme tel plutôt
     * que comme "0 session") : cet écran est derrière EnsureRouterIsConfigured,
     * donc un routeur est forcément enregistré, mais pas forcément allumé.
     */
    private function activeSessionsCount(): ?int
    {
        try {
            return count(MikroTik::hotspot()->getActiveHosts());
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Chiffre d'affaires jour par jour sur les 7 derniers jours, y compris
     * les jours sans vente (à 0), pour un graphique à largeur constante.
     *
     * @return Collection<int, array{label: string, amount: int}>
     */
    private function dailyRevenue(): Collection
    {
        $since = now()->subDays(6)->startOfDay();

        $totalsByDay = Order::query()->paid()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(6, 0))->map(function (int $daysAgo) use ($totalsByDay) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->translatedFormat('d M'),
                'amount' => (int) ($totalsByDay[$date->format('Y-m-d')] ?? 0),
            ];
        });
    }

    /**
     * `barClass` est une classe Tailwind littérale (pas construite
     * dynamiquement à partir de `status->color()`) : Tailwind ne détecte que
     * les classes qui apparaissent explicitement quelque part dans le code
     * source, jamais une chaîne assemblée à l'exécution.
     *
     * @return Collection<int, array{status: OrderStatus, count: int, barClass: string}>
     */
    private function ordersByStatus(): Collection
    {
        $counts = Order::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(OrderStatus::cases())->map(fn (OrderStatus $status) => [
            'status' => $status,
            'count' => (int) ($counts[$status->value] ?? 0),
            'barClass' => match ($status) {
                OrderStatus::Paid => 'bg-lime-500',
                OrderStatus::Pending => 'bg-amber-500',
                OrderStatus::Failed, OrderStatus::Expired => 'bg-red-500',
                OrderStatus::Refunded => 'bg-zinc-400',
            },
        ]);
    }

    /**
     * @return Collection<int, Package>
     */
    private function topPackages(): Collection
    {
        return Package::query()
            ->withCount(['orders as paid_orders_count' => fn ($query) => $query->where('status', OrderStatus::Paid)])
            ->orderByDesc('paid_orders_count')
            ->take(5)
            ->get()
            ->filter(fn (Package $package) => $package->paid_orders_count > 0)
            ->values();
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

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500">Clients actifs</flux:text>
            <flux:heading size="lg">{{ $activeCustomersCount }}</flux:heading>
        </div>

        <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500">Vouchers actifs</flux:text>
            <flux:heading size="lg">{{ $activeVouchersCount }}</flux:heading>
        </div>

        <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:text class="text-sm text-zinc-500">Sessions actives</flux:text>
            <flux:heading size="lg">{{ $activeSessionsCount ?? '—' }}</flux:heading>
            @if ($activeSessionsCount === null)
                <flux:text class="text-sm text-red-500">Routeur injoignable</flux:text>
            @endif
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Ventes des 7 derniers jours</flux:heading>

            @php
                $maxDailyAmount = max($dailyRevenue->max('amount'), 1);
            @endphp

            <div class="flex h-32 items-end gap-2">
                @foreach ($dailyRevenue as $day)
                    <div class="flex flex-1 flex-col items-center gap-2" wire:key="daily-revenue-{{ $loop->index }}">
                        <flux:text class="text-xs text-zinc-500">{{ number_format($day['amount'], 0, ',', ' ') }}</flux:text>
                        <div class="flex h-24 w-full items-end overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-800">
                            <div
                                class="w-full rounded-md bg-[#0F9D8C]"
                                style="height: {{ max((int) round($day['amount'] / $maxDailyAmount * 100), $day['amount'] > 0 ? 6 : 0) }}%"
                            ></div>
                        </div>
                        <flux:text class="text-xs text-zinc-500">{{ $day['label'] }}</flux:text>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Commandes par statut</flux:heading>

            <div class="flex flex-col gap-3">
                @php
                    $maxStatusCount = max($ordersByStatus->max('count'), 1);
                @endphp

                @foreach ($ordersByStatus as $item)
                    <div class="flex flex-col gap-1" wire:key="status-{{ $item['status']->value }}">
                        <div class="flex items-center justify-between gap-2">
                            <flux:badge :color="$item['status']->color()" size="sm">{{ $item['status']->label() }}</flux:badge>
                            <flux:text class="text-sm text-zinc-500">{{ $item['count'] }}</flux:text>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full {{ $item['barClass'] }}" style="width: {{ (int) round($item['count'] / $maxStatusCount * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if ($topPackages->isNotEmpty())
        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Forfaits les plus vendus</flux:heading>

            @php
                $maxPackageSales = max($topPackages->max('paid_orders_count'), 1);
            @endphp

            <div class="flex flex-col gap-3">
                @foreach ($topPackages as $package)
                    <div class="flex flex-col gap-1" wire:key="top-package-{{ $package->id }}">
                        <div class="flex items-center justify-between gap-2">
                            <flux:text class="font-medium">{{ $package->name }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">{{ $package->paid_orders_count }} vente(s)</flux:text>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-[#0F9D8C]" style="width: {{ (int) round($package->paid_orders_count / $maxPackageSales * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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

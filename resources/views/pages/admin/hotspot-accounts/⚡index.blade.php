<?php

use App\Actions\CreateHotspotAccountAction;
use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use App\Models\Package;
use App\Models\VoucherBatch;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use ZillEAli\MikrotikLaravel\Exceptions\ResourceNotFoundException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;

new #[Title('Codes WiFi')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    /**
     * '' = tous, 'generes' = codes créés par l'admin (vouchers à imprimer),
     * 'vendus' = codes achetés en ligne par un client.
     */
    #[Url(as: 'origine')]
    public string $origin = '';

    /**
     * Codes jamais utilisés cochés pour l'impression.
     *
     * @var array<int, string>
     */
    public array $selected = [];

    /**
     * Dernier lot créé, proposé à l'impression juste après sa création.
     */
    public ?int $lastBatchId = null;

    #[Validate('required|exists:packages,id')]
    public ?int $voucherPackageId = null;

    #[Validate('required|integer|min:1|max:50')]
    public int $voucherQuantity = 1;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Coche les codes créés par l'admin et jamais utilisés de la page en
     * cours, en gardant ceux déjà cochés sur d'autres pages.
     */
    public function selectUnusedOnPage(): void
    {
        $pageIds = $this->accountsQuery()
            ->paginate(15)
            ->getCollection()
            ->filter(fn (HotspotAccount $account): bool => $account->isVoucher() && $account->status === HotspotAccountStatus::Active)
            ->map(fn (HotspotAccount $account): string => (string) $account->id);

        $this->selected = collect($this->selected)->merge($pageIds)->unique()->values()->all();
    }

    public function updatedOrigin(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    /**
     * Ouvre le formulaire de génération de vouchers, réinitialisé à chaque
     * ouverture pour ne jamais reproposer la sélection de la fois précédente.
     */
    public function openVoucherModal(): void
    {
        $this->reset(['voucherPackageId', 'voucherQuantity']);
        $this->voucherQuantity = 1;

        Flux::modal('voucher-form')->show();
    }

    /**
     * Génère N comptes Hotspot pour le forfait choisi, sans commande associée
     * (voir CreateHotspotAccountAction) : chaque échec RouterOS individuel est
     * absorbé (le compte concerné est simplement absent du lot), le reste de
     * la génération continue plutôt que d'abandonner tout le lot.
     */
    public function generateVouchers(): void
    {
        $this->validate();

        $package = Package::findOrFail($this->voucherPackageId);
        $action = new CreateHotspotAccountAction;
        $batch = VoucherBatch::create(['package_id' => $package->id, 'quantity' => 0]);

        $created = 0;

        for ($i = 0; $i < $this->voucherQuantity; $i++) {
            if ($action->handle($package, batch: $batch)) {
                $created++;
            }
        }

        Flux::modal('voucher-form')->close();

        if ($created === 0) {
            $batch->delete();
            $this->lastBatchId = null;
        } else {
            $batch->update(['quantity' => $created]);
            $this->lastBatchId = $batch->id;
        }

        if ($created === $this->voucherQuantity) {
            Flux::toast(variant: 'success', text: "{$created} code(s) créé(s), prêts à imprimer.");

            return;
        }

        if ($created > 0) {
            Flux::toast(variant: 'warning', text: "{$created} code(s) sur {$this->voucherQuantity} créé(s), le routeur a refusé les autres.");

            return;
        }

        Flux::toast(variant: 'danger', text: "Impossible de contacter le routeur pour l'instant. Réessayez dans un instant.");
    }

    /**
     * Corbeille (soft delete), pas de suppression définitive ici : le compte
     * reste restaurable depuis l'écran Corbeille, comme les Forfaits et les
     * Clients. Ne touche jamais RouterOS — un compte supprimé ici doit
     * d'abord être suspendu si son accès réseau doit être coupé.
     */
    public function delete(HotspotAccount $account): void
    {
        $account->delete();

        Flux::toast(variant: 'success', text: 'Compte déplacé dans la corbeille.');
    }

    /**
     * Désactive le compte sur RouterOS et coupe immédiatement une éventuelle
     * session en cours, avant de refléter le changement en local — jamais
     * l'inverse : un statut "Suspendu" en base sans effet réel sur le
     * routeur donnerait une fausse confiance à l'admin (le client garderait
     * son accès).
     */
    public function suspend(HotspotAccount $account): void
    {
        try {
            MikroTik::hotspot()->disableUser($account->code);
        } catch (\Throwable $e) {
            $this->reportRouterFailure('suspension', $account, $e);

            return;
        }

        try {
            MikroTik::hotspot()->kickHost($account->code);
        } catch (ResourceNotFoundException) {
            // Pas de session active à couper : le compte est déjà désactivé
            // pour toute nouvelle connexion, ce qui suffit.
        } catch (\Throwable $e) {
            Log::warning('RouterOS: échec de la déconnexion immédiate lors de la suspension', [
                'hotspot_account_id' => $account->id,
                'code' => $account->code,
                'error' => $e->getMessage(),
            ]);
        }

        $account->update(['status' => HotspotAccountStatus::Suspended]);

        Flux::toast(variant: 'success', text: 'Accès suspendu.');
    }

    public function reactivate(HotspotAccount $account): void
    {
        try {
            MikroTik::hotspot()->enableUser($account->code);
        } catch (\Throwable $e) {
            $this->reportRouterFailure('réactivation', $account, $e);

            return;
        }

        $account->update(['status' => HotspotAccountStatus::Active]);

        Flux::toast(variant: 'success', text: 'Accès réactivé.');
    }

    /**
     * Prolonge l'accès de la durée initiale du forfait, à partir de la date
     * d'expiration actuelle si elle n'est pas encore passée, sinon à partir
     * de maintenant. Le `limit-uptime` RouterOS est recalculé comme la durée
     * totale entre l'activation et la nouvelle expiration, pour rester
     * cohérent avec la valeur posée au provisioning
     * (ProvisionHotspotAccountAction) : la prolongation locale n'a d'effet
     * réel que si le routeur reçoit la même nouvelle échéance.
     */
    public function extend(HotspotAccount $account): void
    {
        $account->loadMissing('package');

        $base = $account->expires_at?->isFuture() ? $account->expires_at : now();
        $newExpiresAt = $base->copy()->addMinutes($account->package->duration_minutes);

        $activatedAt = $account->activated_at ?? $account->created_at;
        $limitUptimeSeconds = $activatedAt->diffInSeconds($newExpiresAt);

        try {
            MikroTik::hotspot()->updateUser($account->code, [
                'limit-uptime' => "{$limitUptimeSeconds}s",
            ]);
        } catch (\Throwable $e) {
            $this->reportRouterFailure('prolongation', $account, $e);

            return;
        }

        $account->update(['expires_at' => $newExpiresAt]);

        Flux::toast(variant: 'success', text: 'Accès prolongé.');
    }

    private function reportRouterFailure(string $action, HotspotAccount $account, \Throwable $e): void
    {
        Log::error("RouterOS: échec de la {$action} du compte Hotspot", [
            'hotspot_account_id' => $account->id,
            'code' => $account->code,
            'error' => $e->getMessage(),
        ]);

        Flux::toast(variant: 'danger', text: "Impossible de contacter le routeur pour l'instant. Réessayez dans un instant.");
    }

    /**
     * @return Builder<HotspotAccount>
     */
    private function accountsQuery(): Builder
    {
        return HotspotAccount::query()
            ->with(['package', 'order.customer'])
            ->when($this->origin === 'generes', fn ($query) => $query->whereNull('order_id'))
            ->when($this->origin === 'vendus', fn ($query) => $query->whereNotNull('order_id'))
            ->when($this->search !== '', function ($query) {
                $term = "%{$this->search}%";

                $query->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', $term)
                        ->orWhereHas('package', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('order', fn ($q) => $q->where('phone', 'like', $term)
                            ->orWhereHas('customer', fn ($cq) => $cq->where('email', 'like', $term)));
                });
            })
            ->latest()
            ->latest('id');
    }

    public function with(): array
    {
        return [
            'accounts' => $this->accountsQuery()->paginate(15),
            'packages' => Package::query()->active()->ordered()->get(),
            'batches' => $this->origin === 'generes'
                ? VoucherBatch::query()
                    ->with('package')
                    ->withCount(['hotspotAccounts as unused_count' => fn ($query) => $query->where('status', HotspotAccountStatus::Active)])
                    ->latest()
                    ->limit(6)
                    ->get()
                : collect(),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <x-admin.page-header
        title="Codes WiFi"
        description="Tous les codes d'accès : ceux que vous créez pour les imprimer et les vendre en main propre, et ceux achetés en ligne par vos clients."
    >
        <flux:button variant="primary" icon="plus" wire:click="openVoucherModal">Créer des codes à imprimer</flux:button>
    </x-admin.page-header>

    @if ($lastBatchId)
        <div class="console-card flex flex-wrap items-center justify-between gap-4 p-5">
            <div>
                <flux:heading>Votre lot de codes est prêt</flux:heading>
                <flux:text>Imprimez-le maintenant (15 codes par feuille), ou plus tard depuis « Créés par moi ».</flux:text>
            </div>
            <flux:button
                variant="primary"
                icon="printer"
                :href="route('admin.hotspot-accounts.print', ['lot' => $lastBatchId])"
                target="_blank"
            >
                Imprimer ce lot
            </flux:button>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="console-tabs" role="tablist">
            @foreach (['' => 'Tous', 'generes' => 'Créés par moi', 'vendus' => 'Achetés par les clients'] as $value => $label)
                <button
                    type="button"
                    role="tab"
                    wire:click="$set('origin', '{{ $value }}')"
                    @class(['console-tab', 'console-tab-active' => $origin === $value])
                    aria-selected="{{ $origin === $value ? 'true' : 'false' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Code, forfait, email ou téléphone…" class="sm:w-72" icon="magnifying-glass" />

            @if ($origin === 'generes')
                <flux:button wire:click="selectUnusedOnPage" icon="check-circle">
                    Cocher les non utilisés
                </flux:button>
            @endif

            @if ($origin === 'generes' && $selected !== [])
                <flux:button
                    variant="primary"
                    icon="printer"
                    :href="route('admin.hotspot-accounts.print', ['ids' => $selected])"
                    target="_blank"
                >
                    Imprimer la sélection ({{ count($selected) }})
                </flux:button>
            @endif
        </div>
    </div>

    @if ($batches->isNotEmpty())
        <div class="flex flex-col gap-3">
            <flux:heading>Mes lots</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($batches as $batch)
                    <div wire:key="batch-{{ $batch->id }}" class="console-card flex items-center justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $batch->quantity }} × {{ $batch->package?->name ?? 'Forfait supprimé' }}</p>
                            <p class="text-xs text-[var(--muted-fg)]">
                                Créé le {{ $batch->created_at->translatedFormat('d M Y à H:i') }} · {{ $batch->unused_count }} non utilisé(s)
                            </p>
                        </div>
                        <flux:button
                            size="sm"
                            icon="printer"
                            :href="route('admin.hotspot-accounts.print', ['lot' => $batch->id])"
                            target="_blank"
                        >
                            Imprimer
                        </flux:button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($accounts->isEmpty())
        <div class="console-card p-10 text-center">
            <flux:text>
                {{ $search !== '' ? 'Aucun compte Hotspot ne correspond à cette recherche.' : 'Aucun code pour le moment.' }}
            </flux:text>
        </div>
    @else
        <div class="console-table">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        @if ($origin === 'generes')
                            <th class="w-10"><span class="sr-only">Sélection</span></th>
                        @endif
                        <th>Code</th>
                        <th>Forfait</th>
                        <th>Origine</th>
                        <th>État</th>
                        <th>Fin de l'accès</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts as $account)
                        <tr wire:key="account-{{ $account->id }}">
                            @if ($origin === 'generes')
                                <td>
                                    @if ($account->status === HotspotAccountStatus::Active)
                                        <flux:checkbox wire:model.live="selected" value="{{ $account->id }}" />
                                    @endif
                                </td>
                            @endif
                            <td class="font-mono font-semibold tracking-wider">{{ $account->code }}</td>
                            <td>{{ $account->package?->name ?? '—' }}</td>
                            <td>
                                @if ($account->isVoucher())
                                    <flux:badge color="zinc" size="sm" icon="printer">Créé par moi</flux:badge>
                                @else
                                    {{ $account->order->customer?->email ?? $account->order->phone }}
                                @endif
                            </td>
                            <td>
                                <flux:badge :color="$account->status->color()" size="sm">
                                    {{ $account->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="text-[var(--muted-fg)]">
                                {{ $account->expires_at?->translatedFormat('d M Y à H:i') ?? '—' }}
                            </td>
                            <td>
                                <div class="flex justify-end">
                                    <flux:dropdown align="end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Actions" />

                                        <flux:menu>
                                            <flux:menu.item icon="clock" wire:click="extend({{ $account->id }})">
                                                Prolonger d'une durée de forfait
                                            </flux:menu.item>

                                            @if ($account->status === HotspotAccountStatus::Suspended)
                                                <flux:menu.item icon="play" wire:click="reactivate({{ $account->id }})">
                                                    Réactiver l'accès
                                                </flux:menu.item>
                                            @else
                                                <flux:menu.item icon="pause" variant="danger" wire:click="suspend({{ $account->id }})">
                                                    Couper l'accès
                                                </flux:menu.item>
                                            @endif

                                            <flux:menu.separator />

                                            <flux:menu.item
                                                icon="trash"
                                                variant="danger"
                                                wire:click="delete({{ $account->id }})"
                                                wire:confirm="Déplacer ce code dans les éléments supprimés ?"
                                            >
                                                Supprimer
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $accounts->links() }}
    @endif

    <flux:modal name="voucher-form" class="max-w-lg">
        <form wire:submit="generateVouchers" class="flex flex-col gap-6">
            <div>
                <flux:heading size="lg">Créer des codes à imprimer</flux:heading>
                <flux:text class="mt-1">Chaque code donne accès au WiFi pendant la durée du forfait choisi. Vous pourrez les imprimer juste après.</flux:text>
            </div>

            <flux:field>
                <flux:label>Forfait</flux:label>
                <flux:select wire:model="voucherPackageId">
                    <flux:select.option value="">Choisir un forfait</flux:select.option>
                    @foreach ($packages as $package)
                        <flux:select.option value="{{ $package->id }}">{{ $package->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="voucherPackageId" />
            </flux:field>

            <flux:field>
                <flux:label>Nombre de codes</flux:label>
                <flux:input type="number" wire:model="voucherQuantity" min="1" max="50" />
                <flux:description>50 au maximum par lot.</flux:description>
                <flux:error name="voucherQuantity" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Créer les codes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

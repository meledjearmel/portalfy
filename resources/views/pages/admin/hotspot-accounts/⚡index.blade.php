<?php

use App\Actions\CreateHotspotAccountAction;
use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use App\Models\Package;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use ZillEAli\MikrotikLaravel\Exceptions\ResourceNotFoundException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;

new #[Title('Comptes Hotspot')] class extends Component
{
    use WithPagination;

    #[Validate('required|exists:packages,id')]
    public ?int $voucherPackageId = null;

    #[Validate('required|integer|min:1|max:50')]
    public int $voucherQuantity = 1;

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

        $created = 0;

        for ($i = 0; $i < $this->voucherQuantity; $i++) {
            if ($action->handle($package)) {
                $created++;
            }
        }

        Flux::modal('voucher-form')->close();

        if ($created === $this->voucherQuantity) {
            Flux::toast(variant: 'success', text: "{$created} voucher(s) généré(s).");

            return;
        }

        if ($created > 0) {
            Flux::toast(variant: 'warning', text: "{$created} sur {$this->voucherQuantity} voucher(s) généré(s), le routeur a refusé les autres.");

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

    public function with(): array
    {
        return [
            'accounts' => HotspotAccount::query()
                ->with(['package', 'order.customer'])
                ->latest()
                ->paginate(15),
            'packages' => Package::query()->active()->ordered()->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Comptes Hotspot</flux:heading>
        <flux:button variant="primary" wire:click="openVoucherModal">Générer des vouchers</flux:button>
    </div>

    @if ($accounts->isEmpty())
        <flux:text class="text-zinc-500">Aucun compte Hotspot pour le moment.</flux:text>
    @else
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Code</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Forfait</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Origine</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Statut</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Expire le</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($accounts as $account)
                        <tr wire:key="account-{{ $account->id }}">
                            <td class="px-4 py-3 font-mono">{{ $account->code }}</td>
                            <td class="px-4 py-3">{{ $account->package?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($account->isVoucher())
                                    <flux:badge color="zinc" size="sm">Voucher</flux:badge>
                                @else
                                    {{ $account->order->customer?->email ?? $account->order->phone }}
                                @endif
                            </td>
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

                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        wire:click="delete({{ $account->id }})"
                                        wire:confirm="Déplacer ce compte dans la corbeille ?"
                                    >
                                        Supprimer
                                    </flux:button>
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
            <flux:heading size="lg">Générer des vouchers</flux:heading>

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
                <flux:label>Nombre de vouchers</flux:label>
                <flux:input type="number" wire:model="voucherQuantity" min="1" max="50" />
                <flux:error name="voucherQuantity" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Générer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

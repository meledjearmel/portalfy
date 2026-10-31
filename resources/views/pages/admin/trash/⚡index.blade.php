<?php

use App\Models\Customer;
use App\Models\HotspotAccount;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title("Éléments supprimés")] class extends Component
{
    use WithPagination;

    #[Url]
    public string $model = 'packages';

    /**
     * Modèles soft-deleted concernés par la corbeille (voir docs/execution-plan.md,
     * étape 9) : chacun a ses propres colonnes, donc son propre libellé
     * d'affichage plutôt qu'une colonne générique inexistante partout.
     *
     * @return array<string, array{label: string, class: class-string, display: \Closure}>
     */
    protected function models(): array
    {
        return [
            'packages' => [
                'label' => 'Forfaits',
                'class' => Package::class,
                'display' => fn ($item) => $item->name,
            ],
            'orders' => [
                'label' => 'Commandes',
                'class' => Order::class,
                'display' => fn ($item) => $item->reference,
            ],
            'hotspot_accounts' => [
                'label' => 'Comptes Hotspot',
                'class' => HotspotAccount::class,
                'display' => fn ($item) => $item->code,
            ],
            'customers' => [
                'label' => 'Clients',
                'class' => Customer::class,
                'display' => fn ($item) => $item->email,
            ],
            'invoices' => [
                'label' => 'Factures',
                'class' => Invoice::class,
                'display' => fn ($item) => 'Facture #'.$item->number,
            ],
            'users' => [
                'label' => 'Comptes admin',
                'class' => User::class,
                'display' => fn ($item) => $item->name.' ('.$item->email.')',
            ],
        ];
    }

    public function mount(): void
    {
        if (! array_key_exists($this->model, $this->models())) {
            $this->model = 'packages';
        }
    }

    public function updatedModel(): void
    {
        if (! array_key_exists($this->model, $this->models())) {
            $this->model = 'packages';
        }

        $this->resetPage();
    }

    public function restore(int $id): void
    {
        $config = $this->models()[$this->model];

        $config['class']::onlyTrashed()->findOrFail($id)->restore();

        Flux::toast(variant: 'success', text: 'Élément restauré.');
    }

    public function with(): array
    {
        $config = $this->models()[$this->model];

        return [
            'options' => $this->models(),
            'config' => $config,
            'items' => $config['class']::onlyTrashed()->latest('deleted_at')->paginate(15),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <x-admin.page-header title="Éléments supprimés" description="Ce que vous avez supprimé reste ici et peut être restauré." />

        <flux:select wire:model.live="model" class="max-w-xs">
            @foreach ($options as $key => $option)
                <flux:select.option value="{{ $key }}">{{ $option['label'] }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @if ($items->isEmpty())
        <flux:text class="text-[var(--muted-fg)]">Rien dans la corbeille pour "{{ $config['label'] }}".</flux:text>
    @else
        <div class="console-table">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <th>Élément</th>
                        <th>Supprimé le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr wire:key="trash-{{ $model }}-{{ $item->id }}">
                            <td class="px-4 py-3">{{ ($config['display'])($item) }}</td>
                            <td class="px-4 py-3 text-[var(--muted-fg)]">{{ $item->deleted_at->translatedFormat('d M Y à H:i') }}</td>
                            <td class="px-4 py-3">
                                <flux:button size="sm" variant="ghost" wire:click="restore({{ $item->id }})">
                                    Restaurer
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $items->links() }}
    @endif
</div>

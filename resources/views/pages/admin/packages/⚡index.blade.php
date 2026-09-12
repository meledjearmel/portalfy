<?php

use App\Models\Package;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Forfaits')] class extends Component
{
    public ?Package $editing = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|integer|min:0')]
    public ?int $price = null;

    #[Validate('required|integer|min:1')]
    public ?int $duration_minutes = null;

    #[Validate('nullable|integer|min:1')]
    public ?int $max_speed_mbps = null;

    #[Validate('required|integer|min:1')]
    public int $max_devices = 1;

    #[Validate('nullable|string|max:255')]
    public string $short_description = '';

    #[Validate('boolean')]
    public bool $is_popular = false;

    #[Validate('boolean')]
    public bool $is_active = true;

    #[Validate('required|integer|min:0')]
    public int $sort_order = 0;

    public function with(): array
    {
        return [
            'packages' => Package::query()->ordered()->get(),
        ];
    }

    public function create(): void
    {
        $this->reset([
            'editing', 'name', 'price', 'duration_minutes', 'max_speed_mbps',
            'short_description', 'max_devices', 'is_popular', 'is_active', 'sort_order',
        ]);

        Flux::modal('package-form')->show();
    }

    public function edit(Package $package): void
    {
        $this->editing = $package;
        $this->name = $package->name;
        $this->price = $package->price;
        $this->duration_minutes = $package->duration_minutes;
        $this->max_speed_mbps = $package->max_speed_mbps;
        $this->max_devices = $package->max_devices;
        $this->short_description = $package->short_description ?? '';
        $this->is_popular = $package->is_popular;
        $this->is_active = $package->is_active;
        $this->sort_order = $package->sort_order;

        Flux::modal('package-form')->show();
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            Package::create($data);
        }

        Flux::modal('package-form')->close();
        Flux::toast(variant: 'success', text: 'Forfait enregistré.');
    }

    public function delete(Package $package): void
    {
        $package->delete();

        Flux::toast(variant: 'success', text: 'Forfait déplacé dans la corbeille.');
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Forfaits</flux:heading>
        <flux:button variant="primary" wire:click="create">Ajouter un forfait</flux:button>
    </div>

    @if ($packages->isEmpty())
        <flux:text class="text-zinc-500">Aucun forfait pour le moment.</flux:text>
    @else
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Nom</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Prix</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Durée</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Statut</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($packages as $package)
                        <tr wire:key="package-{{ $package->id }}">
                            <td class="px-4 py-3">
                                {{ $package->name }}
                                @if ($package->is_popular)
                                    <flux:badge size="sm" color="lime">Populaire</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ number_format($package->price, 0, ',', ' ') }} F</td>
                            <td class="px-4 py-3">{{ $package->duration_label }}</td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$package->is_active ? 'lime' : 'zinc'" size="sm">
                                    {{ $package->is_active ? 'Actif' : 'Inactif' }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <flux:button size="sm" variant="ghost" wire:click="edit({{ $package->id }})">
                                        Modifier
                                    </flux:button>
                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        wire:click="delete({{ $package->id }})"
                                        wire:confirm="Supprimer ce forfait ?"
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
    @endif

    <flux:modal name="package-form" class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:heading size="lg">{{ $editing ? 'Modifier le forfait' : 'Ajouter un forfait' }}</flux:heading>

            <flux:field>
                <flux:label>Nom</flux:label>
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Prix (F CFA)</flux:label>
                    <flux:input type="number" wire:model="price" min="0" />
                    <flux:error name="price" />
                </flux:field>

                <flux:field>
                    <flux:label>Durée (minutes)</flux:label>
                    <flux:input type="number" wire:model="duration_minutes" min="1" />
                    <flux:error name="duration_minutes" />
                </flux:field>

                <flux:field>
                    <flux:label>Débit max (Mbps)</flux:label>
                    <flux:input type="number" wire:model="max_speed_mbps" min="1" placeholder="Illimité" />
                    <flux:error name="max_speed_mbps" />
                </flux:field>

                <flux:field>
                    <flux:label>Appareils simultanés</flux:label>
                    <flux:input type="number" wire:model="max_devices" min="1" />
                    <flux:error name="max_devices" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Description courte</flux:label>
                <flux:input wire:model="short_description" />
                <flux:error name="short_description" />
            </flux:field>

            <flux:field>
                <flux:label>Ordre d'affichage</flux:label>
                <flux:input type="number" wire:model="sort_order" min="0" />
                <flux:error name="sort_order" />
            </flux:field>

            <div class="flex gap-6">
                <flux:checkbox wire:model="is_popular" label="Populaire" />
                <flux:checkbox wire:model="is_active" label="Actif" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

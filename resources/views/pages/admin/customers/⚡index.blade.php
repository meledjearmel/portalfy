<?php

use App\Enums\CustomerStatus;
use App\Models\Customer;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Clients')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public ?Customer $editing = null;

    public string $email = '';

    public string $phone = '';

    public CustomerStatus $status = CustomerStatus::Active;

    public string $password = '';

    public string $password_confirmation = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Customer::class)->ignore($this->editing?->id)->whereNull('deleted_at')],
            'phone' => ['required', 'regex:/^(01|05|07)\d{8}$/', Rule::unique(Customer::class)->ignore($this->editing?->id)->whereNull('deleted_at')],
            'status' => ['required'],
            // Optionnel à la modification (laisser vide = mot de passe inchangé),
            // obligatoire à la création : voir CreateNewUser pour les mêmes règles
            // côté inscription publique.
            'password' => [$this->editing ? 'nullable' : 'required', 'string', Password::default(), 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.regex' => 'Entrez un numéro ivoirien valide (10 chiffres, commençant par 01, 05 ou 07).',
        ];
    }

    public function create(): void
    {
        $this->reset(['editing', 'email', 'phone', 'password', 'password_confirmation']);
        $this->status = CustomerStatus::Active;

        Flux::modal('customer-form')->show();
    }

    public function edit(Customer $customer): void
    {
        $this->editing = $customer;
        $this->email = $customer->email;
        $this->phone = $customer->phone;
        $this->status = $customer->status;
        $this->password = '';
        $this->password_confirmation = '';

        Flux::modal('customer-form')->show();
    }

    public function save(): void
    {
        $data = $this->validate($this->rules(), $this->messages());

        if (blank($data['password'])) {
            unset($data['password']);
        }

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            Customer::create($data);
        }

        Flux::modal('customer-form')->close();
        Flux::toast(variant: 'success', text: 'Client enregistré.');
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();

        Flux::toast(variant: 'success', text: 'Client déplacé dans la corbeille.');
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

        <div class="flex gap-2">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Rechercher par email ou téléphone..." class="max-w-xs" icon="magnifying-glass" />
            <flux:button variant="primary" wire:click="create">Ajouter un client</flux:button>
        </div>
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
                        <th class="px-4 py-3 text-start font-medium text-zinc-500">Actions</th>
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
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <flux:button size="sm" variant="ghost" :href="route('admin.customers.show', $customer)" wire:navigate>
                                        Voir
                                    </flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="edit({{ $customer->id }})">
                                        Modifier
                                    </flux:button>
                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        wire:click="delete({{ $customer->id }})"
                                        wire:confirm="Déplacer ce client dans la corbeille ?"
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

        {{ $customers->links() }}
    @endif

    <flux:modal name="customer-form" class="max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:heading size="lg">{{ $editing ? 'Modifier le client' : 'Ajouter un client' }}</flux:heading>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Téléphone</flux:label>
                <flux:input wire:model="phone" placeholder="0700000000" />
                <flux:error name="phone" />
            </flux:field>

            <flux:field>
                <flux:label>Statut</flux:label>
                <flux:select wire:model="status">
                    @foreach (CustomerStatus::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="status" />
            </flux:field>

            <flux:field>
                <flux:label>{{ $editing ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe' }}</flux:label>
                <flux:input type="password" wire:model="password" viewable />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>Confirmer le mot de passe</flux:label>
                <flux:input type="password" wire:model="password_confirmation" viewable />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Annuler</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

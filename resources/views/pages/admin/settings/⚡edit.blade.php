<?php

use App\Enums\CredentialMode;
use App\Models\WifiZoneSetting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Paramètres')] class extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $slogan = '';

    #[Validate('nullable|string|max:2000')]
    public string $description = '';

    #[Validate('required|string|max:9')]
    public string $color_primary = '#0F9D8C';

    #[Validate('required|string|max:9')]
    public string $color_secondary = '#0B2B26';

    #[Validate('required|string|max:9')]
    public string $color_accent = '#0F9D8C';

    #[Validate('nullable|string|max:255')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $whatsapp = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:255')]
    public string $address = '';

    #[Validate('nullable|string|max:255')]
    public string $opening_hours = '';

    #[Validate('nullable|string|max:5000')]
    public string $terms = '';

    #[Validate('nullable|string|max:5000')]
    public string $privacy_policy = '';

    #[Validate('required')]
    public CredentialMode $credential_mode = CredentialMode::Unique;

    #[Validate('nullable|image|max:2048')]
    public $logo = null;

    #[Validate('nullable|image|max:4096')]
    public $background = null;

    public function mount(): void
    {
        $zone = WifiZoneSetting::current();

        $this->name = $zone->name;
        $this->slogan = $zone->slogan ?? '';
        $this->description = $zone->description ?? '';
        $this->color_primary = $zone->color_primary;
        $this->color_secondary = $zone->color_secondary;
        $this->color_accent = $zone->color_accent;
        $this->phone = $zone->phone ?? '';
        $this->whatsapp = $zone->whatsapp ?? '';
        $this->email = $zone->email ?? '';
        $this->address = $zone->address ?? '';
        $this->opening_hours = $zone->opening_hours ?? '';
        $this->terms = $zone->terms ?? '';
        $this->privacy_policy = $zone->privacy_policy ?? '';
        $this->credential_mode = $zone->credential_mode;
    }

    public function save(): void
    {
        $data = $this->validate();

        unset($data['logo'], $data['background']);

        $zone = WifiZoneSetting::current();

        if ($this->logo) {
            $data['logo_path'] = $this->logo->store('wifi-zone', 'public');
        }

        if ($this->background) {
            $data['background_path'] = $this->background->store('wifi-zone', 'public');
        }

        $zone->update($data);

        $this->reset(['logo', 'background']);

        Flux::toast(variant: 'success', text: 'Paramètres enregistrés.');
    }
};
?>

<div class="flex flex-col gap-6">
    <flux:heading size="xl">Paramètres</flux:heading>

    <form wire:submit="save" class="flex flex-col gap-8">
        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Identité</flux:heading>

            <flux:field>
                <flux:label>Nom de la zone</flux:label>
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Slogan</flux:label>
                <flux:input wire:model="slogan" />
                <flux:error name="slogan" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Logo</flux:label>
                    <input type="file" wire:model="logo" accept="image/*" />
                    <flux:error name="logo" />
                </flux:field>

                <flux:field>
                    <flux:label>Fond de page</flux:label>
                    <input type="file" wire:model="background" accept="image/*" />
                    <flux:error name="background" />
                </flux:field>
            </div>
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Couleurs</flux:heading>

            <div class="grid grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Primaire</flux:label>
                    <flux:input type="color" wire:model="color_primary" />
                    <flux:error name="color_primary" />
                </flux:field>

                <flux:field>
                    <flux:label>Secondaire</flux:label>
                    <flux:input type="color" wire:model="color_secondary" />
                    <flux:error name="color_secondary" />
                </flux:field>

                <flux:field>
                    <flux:label>Accent</flux:label>
                    <flux:input type="color" wire:model="color_accent" />
                    <flux:error name="color_accent" />
                </flux:field>
            </div>
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Contact</flux:heading>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Téléphone</flux:label>
                    <flux:input wire:model="phone" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>WhatsApp</flux:label>
                    <flux:input wire:model="whatsapp" />
                    <flux:error name="whatsapp" />
                </flux:field>

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="email" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Horaires</flux:label>
                    <flux:input wire:model="opening_hours" />
                    <flux:error name="opening_hours" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Adresse</flux:label>
                <flux:input wire:model="address" />
                <flux:error name="address" />
            </flux:field>
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Accès Hotspot</flux:heading>

            <flux:field>
                <flux:label>Mode d'identifiants</flux:label>
                <flux:select wire:model="credential_mode">
                    <flux:select.option value="{{ CredentialMode::Unique->value }}">
                        Identique (code = identifiant = mot de passe)
                    </flux:select.option>
                    <flux:select.option value="{{ CredentialMode::Separate->value }}">
                        Séparé (mot de passe distinct du code)
                    </flux:select.option>
                </flux:select>
                <flux:error name="credential_mode" />
            </flux:field>
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Textes légaux</flux:heading>

            <flux:field>
                <flux:label>CGU</flux:label>
                <flux:textarea wire:model="terms" rows="4" />
                <flux:error name="terms" />
            </flux:field>

            <flux:field>
                <flux:label>Politique de confidentialité</flux:label>
                <flux:textarea wire:model="privacy_policy" rows="4" />
                <flux:error name="privacy_policy" />
            </flux:field>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Enregistrer</flux:button>
        </div>
    </form>
</div>

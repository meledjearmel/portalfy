<?php

use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title("J'ai déjà un code")] class extends Component
{
    public string $code = '';

    public ?HotspotAccount $account = null;

    public function verify(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $this->account = null;

        $account = HotspotAccount::query()
            ->where('code', trim($this->code))
            ->first();

        if (! $account) {
            $this->addError('code', "Ce code n'est pas valide.");

            return;
        }

        $message = match ($account->status) {
            HotspotAccountStatus::Used => 'Ce code a déjà été utilisé.',
            HotspotAccountStatus::Expired => 'Ce code a expiré.',
            HotspotAccountStatus::Suspended => 'Cet accès a été suspendu.',
            HotspotAccountStatus::Active => null,
        };

        if ($message !== null) {
            $this->addError('code', $message);

            return;
        }

        $this->account = $account;
    }
};
?>

<div class="mx-auto flex min-h-[60vh] w-full max-w-md flex-col items-center justify-center gap-8">
    <div class="flex flex-col items-center gap-2 text-center">
        <flux:badge size="sm" class="glass-button">Accès existant</flux:badge>
        <flux:heading size="xl" class="glass-text text-3xl font-extrabold">J'ai déjà un code</flux:heading>
    </div>

    @if ($account)
        <div class="glass-card flex w-full flex-col items-center gap-3 p-8 text-center">
            <flux:text class="glass-text text-sm opacity-80">Votre code</flux:text>
            <flux:heading size="xl" class="glass-text text-3xl font-extrabold tracking-widest">
                {{ $account->code }}
            </flux:heading>

            @if ($account->expires_at)
                <flux:text class="glass-text text-sm opacity-80">
                    Valide jusqu'au {{ $account->expires_at->translatedFormat('d M Y à H:i') }}
                </flux:text>
            @endif

            <flux:button variant="ghost" class="glass-button mt-2 w-full" disabled>
                Se connecter au WiFi
            </flux:button>
        </div>
    @else
        <form wire:submit="verify" class="glass-card flex w-full flex-col items-center gap-6 p-8">
            <div class="flex flex-col items-center gap-3">
                <flux:text class="glass-text text-sm font-medium opacity-90">Entrez votre code à 6 caractères</flux:text>
                <flux:otp length="6" wire:model="code" autofocus />
                <flux:error name="code" />
            </div>

            <flux:button type="submit" variant="ghost" class="glass-button w-full">
                Valider mon code
            </flux:button>
        </form>
    @endif
</div>

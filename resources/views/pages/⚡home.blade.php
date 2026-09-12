<?php

use App\Actions\CaptureHotspotContextAction;
use App\Actions\MapHotspotLoginErrorAction;
use App\Models\WifiZoneSetting;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.public')] class extends Component
{
    public ?string $loginError = null;

    public function mount(
        CaptureHotspotContextAction $captureHotspotContext,
        MapHotspotLoginErrorAction $mapHotspotLoginError,
        Request $request,
    ): void {
        $captureHotspotContext->handle($request);

        $error = $request->query('error');

        if (is_string($error) && $error !== '') {
            $this->loginError = $mapHotspotLoginError->handle($error);
        }
    }

    public function with(): array
    {
        return [
            'zone' => WifiZoneSetting::current(),
        ];
    }
};
?>

<div class="flex min-h-[70vh] flex-col items-center justify-center gap-12">
    @if ($loginError)
        <div class="glass-card mx-auto flex w-full max-w-2xl items-center gap-3 border-l-4 border-l-[#D6483F] p-4 text-left">
            <flux:icon name="exclamation-triangle" class="glass-text size-6 shrink-0" />
            <flux:text class="glass-text">{{ $loginError }}</flux:text>
        </div>
    @endif

    <div class="glass-card mx-auto flex w-full max-w-2xl flex-col items-center gap-6 p-8 text-center sm:p-12">
        <flux:badge size="sm" icon="check-circle" class="glass-button">Réseau actif</flux:badge>

        <flux:heading size="xl" class="glass-text text-4xl font-extrabold sm:text-5xl">
            Bienvenue chez <span class="brand-text-shine">{{ $zone->name }}</span>
        </flux:heading>

        @if ($zone->slogan)
            <flux:text class="glass-text text-lg opacity-90">{{ $zone->slogan }}</flux:text>
        @endif

        <div class="flex w-full max-w-xs flex-col gap-3">
            <flux:button :href="route('packages.index')" wire:navigate variant="ghost" class="glass-button w-full">
                Acheter un accès
            </flux:button>

            <flux:button :href="route('hotspot-access.show')" wire:navigate variant="ghost" class="glass-text w-full">
                J'ai déjà un code
            </flux:button>
        </div>
    </div>

    <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-center gap-4">
        <div class="glass-card glass-text flex items-center gap-2 px-4 py-2 text-sm font-medium">
            <flux:icon name="shield-check" class="size-5" />
            Connexion sécurisée
        </div>
        <div class="glass-card glass-text flex items-center gap-2 px-4 py-2 text-sm font-medium">
            <flux:icon name="bolt" class="size-5" />
            Ultra rapide
        </div>
        <div class="glass-card glass-text flex items-center gap-2 px-4 py-2 text-sm font-medium">
            <flux:icon name="wifi" class="size-5" />
            Sans engagement
        </div>
        <div class="glass-card glass-text flex items-center gap-2 px-4 py-2 text-sm font-medium">
            <flux:icon name="clock" class="size-5" />
            Forfaits flexibles
        </div>
    </div>
</div>

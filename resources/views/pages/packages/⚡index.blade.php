<?php

use App\Models\Package;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Nos forfaits')] class extends Component
{
    /**
     * @return array{packages: Collection<int, Package>}
     */
    public function with(): array
    {
        return [
            'packages' => Package::active()->ordered()->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-10">
    <div class="mx-auto flex max-w-xl flex-col items-center gap-3 text-center">
        <flux:badge size="sm" class="glass-button">Nos forfaits</flux:badge>
        <flux:heading size="xl" class="glass-text text-3xl font-extrabold sm:text-4xl">Choisissez votre forfait</flux:heading>
        <flux:text class="glass-text text-lg opacity-90">
            Un accès simple, sans engagement, valable dès l'achat.
        </flux:text>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        @foreach ($packages as $package)
            <div wire:key="package-{{ $package->id }}" class="glass-card relative flex flex-col gap-5 p-6">
                @if ($package->is_popular)
                    <flux:badge class="absolute! -top-3 left-1/2 z-20! -translate-x-1/2 border-none bg-[#0F9D8C] text-white">
                        Populaire
                    </flux:badge>
                @endif

                <div class="flex flex-col gap-1">
                    <flux:text class="glass-text text-sm font-semibold opacity-80">{{ $package->name }}</flux:text>
                    <flux:heading size="xl" class="glass-text text-3xl font-extrabold">
                        {{ number_format($package->price, 0, ',', ' ') }} F
                    </flux:heading>
                    <flux:text class="glass-text text-sm opacity-80">Valable {{ $package->duration_label }}</flux:text>
                </div>

                @if ($package->short_description)
                    <flux:text class="glass-text text-sm opacity-90">{{ $package->short_description }}</flux:text>
                @endif

                <div class="h-px bg-white/25"></div>

                <ul class="glass-text flex flex-col gap-2 text-sm opacity-90">
                    <li class="flex items-center gap-2">
                        <flux:icon name="device-phone-mobile" class="size-4" />
                        {{ $package->max_devices }} appareil(s) simultané(s)
                    </li>
                    @if ($package->max_speed_mbps)
                        <li class="flex items-center gap-2">
                            <flux:icon name="bolt" class="size-4" />
                            Jusqu'à {{ $package->max_speed_mbps }} Mbps
                        </li>
                    @endif
                </ul>

                <flux:button
                    :href="route('orders.create', ['package' => $package])"
                    wire:navigate
                    variant="ghost"
                    class="glass-button mt-auto w-full"
                >
                    Choisir ce forfait
                </flux:button>
            </div>
        @endforeach
    </div>
</div>

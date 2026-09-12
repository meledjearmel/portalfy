<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.public')] #[Title('Mes factures')] class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return [
            'invoices' => Auth::guard('customer')->user()
                ->invoices()
                ->with('order.package')
                ->latest()
                ->paginate(10),
        ];
    }
};
?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-8">
    <div class="flex flex-col items-center gap-2 text-center">
        <flux:badge size="sm" class="glass-button">Mon compte</flux:badge>
        <flux:heading size="xl" class="glass-text text-3xl font-extrabold">Mes factures</flux:heading>
        <flux:text class="glass-text opacity-90">
            Téléchargez vos factures à tout moment. Elles ne vous sont jamais envoyées par email.
        </flux:text>
    </div>

    @if ($invoices->isEmpty())
        <div class="glass-card flex flex-col items-center gap-3 p-8 text-center">
            <flux:text class="glass-text opacity-90">Vous n'avez encore aucune facture.</flux:text>
        </div>
    @else
        <div class="flex flex-col gap-4">
            @foreach ($invoices as $invoice)
                <div class="glass-card flex items-center justify-between gap-4 p-6" wire:key="invoice-{{ $invoice->id }}">
                    <div class="flex flex-col gap-1">
                        <flux:text class="glass-text font-medium">Facture #{{ $invoice->number }}</flux:text>
                        <flux:text class="glass-text text-sm opacity-70">
                            {{ $invoice->created_at->translatedFormat('d M Y') }} · {{ number_format($invoice->amount, 0, ',', ' ') }} F
                        </flux:text>
                    </div>

                    <flux:button
                        href="{{ route('account.invoices.download', $invoice) }}"
                        variant="ghost"
                        class="glass-button"
                        icon="arrow-down-tray"
                    >
                        Télécharger
                    </flux:button>
                </div>
            @endforeach
        </div>

        {{ $invoices->links() }}
    @endif
</div>

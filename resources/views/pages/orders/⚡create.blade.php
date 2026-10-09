<?php

use App\Contracts\PaymentGatewayContract;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Package;
use GeniusPay\Laravel\Exceptions\GeniusPayException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Récapitulatif')] class extends Component
{
    public Package $package;

    public string $phone = '';

    public string $paymentMethod = '';

    public function mount(Package $package): void
    {
        $this->package = $package;

        if ($customer = Auth::guard('customer')->user()) {
            $this->phone = $customer->phone;
        }
    }

    public function pay(PaymentGatewayContract $gateway): void
    {
        $this->phone = preg_replace('/\D/', '', $this->phone);

        $this->validate([
            'phone' => ['required', 'regex:/^(01|05|07)\d{8}$/'],
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
        ], [
            'paymentMethod.required' => 'Choisissez un moyen de paiement.',
            'phone.regex' => 'Entrez un numéro ivoirien valide (10 chiffres, commençant par 01, 05 ou 07).',
        ]);

        $order = Order::create([
            'reference' => Str::upper(Str::random(10)),
            'package_id' => $this->package->id,
            'customer_id' => Auth::guard('customer')->id(),
            'phone' => $this->phone,
            'amount' => $this->package->price,
            'status' => OrderStatus::Pending,
        ]);

        try {
            $initiation = $gateway->createPayment($order, PaymentMethod::from($this->paymentMethod));
        } catch (GeniusPayException $e) {
            $order->forceDelete();

            $this->addError('phone', "Impossible de démarrer le paiement pour le moment. Réessayez dans quelques instants.");

            return;
        }

        $order->update([
            'payment_provider' => $initiation->provider,
            'payment_reference' => $initiation->reference,
        ]);

        // URL externe (GeniusPay) : navigation classique, jamais via wire:navigate.
        $this->redirect($initiation->checkoutUrl, navigate: false);
    }
};
?>

<div class="mx-auto flex w-full max-w-md flex-col gap-8">
    <div class="flex flex-col items-center gap-2 text-center">
        <flux:badge size="sm" class="glass-button">Récapitulatif</flux:badge>
        <flux:heading size="xl" class="glass-text text-3xl font-extrabold">Finalisez votre achat</flux:heading>
    </div>

    <div class="glass-card flex flex-col gap-4 p-6">
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-col">
                <flux:text class="glass-text text-sm font-semibold opacity-80">{{ $package->name }}</flux:text>
                <flux:text class="glass-text text-sm opacity-80">Valable {{ $package->duration_label }}</flux:text>
            </div>
            <flux:heading size="xl" class="glass-text text-2xl font-extrabold">
                {{ number_format($package->price, 0, ',', ' ') }} F
            </flux:heading>
        </div>
    </div>

    <form wire:submit="pay" class="flex flex-col gap-6">
        <div
            class="glass-card flex flex-col items-center gap-2 p-6 text-center"
            x-data="{
                phone: $wire.entangle('phone'),
                touched: false,
                sanitize(value) {
                    return value.replace(/\D/g, '').slice(0, 10);
                },
                get error() {
                    if (! this.touched || this.phone === '') {
                        return '';
                    }

                    if (! /^(01|05|07)/.test(this.phone)) {
                        return 'Le numéro doit commencer par 01, 05 ou 07.';
                    }

                    return this.phone.length < 10 ? 'Le numéro doit contenir 10 chiffres.' : '';
                },
            }"
            x-init="phone = sanitize(phone ?? '')"
        >
            <label for="phone" class="glass-text text-sm font-semibold">Numéro de téléphone</label>

            <input
                id="phone"
                type="tel"
                inputmode="numeric"
                autocomplete="tel-national"
                maxlength="10"
                placeholder="07 00 00 00 00"
                x-model="phone"
                x-on:input="phone = $event.target.value = sanitize($event.target.value)"
                x-on:blur="touched = true"
                x-bind:aria-invalid="error !== ''"
                aria-describedby="phone-error"
                class="w-full rounded-xl border border-white/40 bg-white/15 px-4 py-3 text-center text-lg font-semibold tracking-[0.2em] text-white placeholder:text-white/50 focus:border-white focus:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/60 aria-invalid:border-[#D6483F] aria-invalid:ring-[#D6483F]/60"
            />

            <p class="glass-text text-xs opacity-80"><span x-text="phone.length">0</span>/10 chiffres</p>

            <p id="phone-error" class="text-sm font-medium text-white" role="alert" x-show="error" x-text="error" x-cloak></p>

            @error('phone')
                <p class="text-sm font-medium text-white" role="alert" x-show="! error">{{ $message }}</p>
            @enderror
        </div>

        <div class="glass-card flex flex-col gap-4 p-6">
            <flux:heading class="glass-text text-center">Moyen de paiement</flux:heading>

            <div class="grid grid-cols-3 gap-3" role="radiogroup" aria-label="Moyen de paiement">
                @foreach (PaymentMethod::cases() as $method)
                    <label
                        wire:key="payment-method-{{ $method->value }}"
                        class="relative flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-2 py-3 text-center transition-colors hover:bg-white/20 has-checked:border-white has-checked:bg-white/25 has-checked:ring-2 has-checked:ring-white has-focus-visible:ring-2 has-focus-visible:ring-white"
                    >
                        <input type="radio" wire:model="paymentMethod" value="{{ $method->value }}" class="peer sr-only" />

                        <span class="flex size-12 items-center justify-center overflow-hidden rounded-full bg-white p-1.5 shadow-sm">
                            <img src="{{ $method->logo() }}" alt="" class="size-full object-contain" width="48" height="48" loading="lazy" />
                        </span>

                        <span class="glass-text text-xs font-semibold leading-tight">{{ $method->label() }}</span>

                        <flux:icon name="check-circle" variant="solid" class="absolute top-1.5 right-1.5 hidden size-5 text-white peer-checked:block" />
                    </label>
                @endforeach
            </div>

            <flux:error name="paymentMethod" class="text-center" />
        </div>

        <flux:button type="submit" variant="ghost" class="glass-button w-full">
            Payer maintenant
        </flux:button>
    </form>
</div>

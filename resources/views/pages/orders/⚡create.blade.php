<?php

use App\Contracts\PaymentGatewayContract;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Package;
use GeniusPay\Laravel\Exceptions\GeniusPayException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Récapitulatif')] class extends Component
{
    public Package $package;

    public string $phone = '';

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
        ], [
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
            $initiation = $gateway->createPayment($order);
        } catch (GeniusPayException $e) {
            $order->forceDelete();

            $this->addError('phone', "Impossible de démarrer le paiement pour le moment. Réessayez dans quelques instants.");

            return;
        }

        $order->update([
            'payment_provider' => $initiation->provider,
            'payment_reference' => $initiation->reference,
        ]);

        $this->redirect($initiation->checkoutUrl);
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
        <div class="glass-card flex flex-col items-center gap-2 p-6 text-center">
            <flux:field class="items-center text-center">
                <flux:label class="glass-text text-center">Numéro de téléphone</flux:label>
                <flux:input
                    type="tel"
                    inputmode="numeric"
                    wire:model="phone"
                    placeholder="07 00 00 00 00"
                    x-mask="'99 99 99 99 99'"
                    class="text-center"
                />
                <flux:error name="phone" />
            </flux:field>
        </div>

        <div class="glass-card flex items-start gap-3 p-4">
            <flux:icon name="information-circle" class="glass-text size-5 shrink-0" />
            <flux:text class="glass-text text-sm opacity-90">
                Le choix du moyen de paiement (Wave, Orange Money, MTN Money, carte)
                se fait sur la page suivante.
            </flux:text>
        </div>

        <flux:button type="submit" variant="ghost" class="glass-button w-full">
            Payer maintenant
        </flux:button>
    </form>
</div>

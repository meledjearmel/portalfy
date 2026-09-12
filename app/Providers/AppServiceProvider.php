<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayContract;
use App\Models\WifiZoneSetting;
use App\Services\GeniusPayGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayContract::class, GeniusPayGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureViewComposers();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Rendre l'identité de la WiFi Zone disponible dans le layout public,
     * sans jamais la coder en dur dans les vues.
     */
    protected function configureViewComposers(): void
    {
        View::composer('layouts.public', function ($view): void {
            $zone = WifiZoneSetting::current();

            $view->with('wifiZoneSetting', $zone);

            // La barre de progression wire:navigate reprend la couleur
            // primaire choisie par l'admin, jamais une valeur codée en dur.
            config(['livewire.navigate.progress_bar_color' => $zone->color_primary]);
        });
    }
}

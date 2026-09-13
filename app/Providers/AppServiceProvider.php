<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayContract;
use App\Models\RouterSetting;
use App\Models\WifiZoneSetting;
use App\Services\GeniusPayGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use ZillEAli\MikrotikLaravel\MikrotikManager;

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
        $this->configureMikrotikConnection();
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
        // Selon comment la vue est chargée, Livewire lui attribue un nom
        // différent : #[Layout('layouts.public')] résout "layouts.public",
        // mais <x-layouts::public> (vues Blade classiques, ex. écrans
        // Fortify) résout un namespace haché propre au dossier
        // (ex. "f4ac99e09542ff494432bc959d4fee61::public"), jamais
        // "layouts::public" littéralement. On matche donc sur le chemin
        // du fichier, seul identifiant stable dans les deux cas.
        View::composer('*', function ($view): void {
            $path = str_replace('\\', '/', $view->getPath());

            if (! str_ends_with($path, 'resources/views/layouts/public.blade.php')) {
                return;
            }

            $zone = WifiZoneSetting::current();

            $view->with('wifiZoneSetting', $zone);

            // La barre de progression wire:navigate reprend la couleur
            // primaire choisie par l'admin, jamais une valeur codée en dur.
            config(['livewire.navigate.progress_bar_color' => $zone->color_primary]);
        });
    }

    /**
     * Fait passer la connexion RouterOS de la config statique (mikrotik.php,
     * donc .env) à la configuration enregistrée par l'admin (RouterSetting) :
     * on ré-enregistre le binding singleton de mikrotik-laravel après lui,
     * plutôt que de le forker, pour continuer à bénéficier du reste de sa
     * config (retry, timeouts socket...). Le rebind est un simple remplacement
     * de closure — sans effet tant qu'aucun code n'a encore résolu
     * MikrotikManager depuis le conteneur, ce qui n'arrive jamais avant la fin
     * du boot() de tous les providers.
     */
    protected function configureMikrotikConnection(): void
    {
        $this->app->singleton(MikrotikManager::class, function ($app) {
            return new MikrotikManager([
                ...$app['config']['mikrotik'],
                ...RouterSetting::current()->toMikrotikConfig(),
            ]);
        });
    }
}

<?php

use App\Models\RouterSetting;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\MikrotikManager;

new #[Title('Routeur')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $host = '';

    #[Validate('required|integer|min:1|max:65535')]
    public ?int $port = null;

    #[Validate('required|string|max:255')]
    public string $username = '';

    #[Validate('nullable|string|max:255')]
    public string $password = '';

    #[Validate('required|integer|min:1|max:120')]
    public int $timeout = 10;

    #[Validate('boolean')]
    public bool $use_ssl = false;

    /**
     * Charge la configuration enregistrée dans le formulaire et ouvre le
     * modal — jamais l'inverse : la connexion en cours (celle affichée sur
     * l'écran) doit rester la référence tant que l'admin n'a pas validé de
     * nouvelles valeurs.
     */
    public function configure(): void
    {
        $setting = RouterSetting::current();

        $this->host = $setting->host ?? '';
        $this->port = $setting->port;
        $this->username = $setting->username ?? '';
        $this->password = $setting->password ?? '';
        $this->timeout = $setting->timeout;
        $this->use_ssl = $setting->use_ssl;

        Flux::modal('router-settings-form')->show();
    }

    public function saveSettings(): void
    {
        $data = $this->validate();

        RouterSetting::current()->update($data);

        Flux::modal('router-settings-form')->close();
        Flux::toast(variant: 'success', text: 'Configuration du routeur enregistrée.');
    }

    /**
     * Essaie de se connecter avec les valeurs actuellement saisies dans le
     * formulaire (pas encore enregistrées) : une instance jetable de
     * MikrotikManager, jamais celle partagée par le conteneur/la façade
     * MikroTik, pour ne jamais faire pointer le reste de l'écran (with(),
     * kickSession) vers ce routeur candidat tant qu'il n'a pas été enregistré.
     */
    public function testConnection(): void
    {
        $data = $this->validate();

        try {
            $identity = (new MikrotikManager([
                ...config('mikrotik'),
                'host' => $data['host'],
                'port' => $data['port'],
                'username' => $data['username'],
                'password' => $data['password'],
                'timeout' => $data['timeout'],
                'ssl' => $data['use_ssl'],
            ]))->system()->getIdentity();
        } catch (\Throwable $e) {
            Log::warning('RouterOS: échec du test de connexion depuis le formulaire de configuration', [
                'host' => $data['host'],
                'port' => $data['port'],
                'error' => $e->getMessage(),
            ]);

            Flux::toast(variant: 'danger', text: "Connexion impossible. Vérifiez l'adresse, le port et les identifiants.");

            return;
        }

        Flux::toast(variant: 'success', text: "Connexion réussie — routeur « {$identity} ».");
    }

    /**
     * Coupe une session Hotspot active depuis cette vue "live" du routeur
     * (pas un HotspotAccount facturé) : il n'y a ici aucun statut local à
     * répercuter, seulement l'ordre de déconnexion envoyé à RouterOS.
     */
    public function kickSession(string $user): void
    {
        try {
            MikroTik::hotspot()->kickHost($user);
        } catch (\Throwable $e) {
            Log::error("RouterOS: échec de la déconnexion manuelle de la session {$user}", [
                'user' => $user,
                'error' => $e->getMessage(),
            ]);

            Flux::toast(variant: 'danger', text: "Impossible de contacter le routeur pour l'instant. Réessayez dans un instant.");

            return;
        }

        Flux::toast(variant: 'success', text: 'Session déconnectée.');
    }

    /**
     * Toutes les données de l'écran proviennent du même routeur : si la
     * première requête échoue (routeur éteint, injoignable, identifiants API
     * invalides), on bascule tout l'écran sur un état "injoignable" plutôt
     * que de laisser des variables partiellement vides faire planter la vue.
     *
     * Si aucun routeur n'a même été enregistré (RouterSetting vide), on
     * n'essaie même pas de se connecter : c'est un état distinct
     * ("à configurer", pas "injoignable"), et c'est aussi l'état qui explique
     * pourquoi EnsureRouterIsConfigured a pu rediriger l'admin ici depuis
     * n'importe quelle autre page de l'administration.
     *
     * @return array<string, mixed>
     */
    public function with(): array
    {
        if (! RouterSetting::current()->isConfigured()) {
            return [
                'routerOnline' => false,
                'routerConfigured' => false,
                'identity' => null,
                'resources' => [],
                'interfaces' => [],
                'activeSessions' => [],
            ];
        }

        try {
            $identity = MikroTik::system()->getIdentity();
            $resources = MikroTik::system()->getResources();
            $interfaces = MikroTik::interfaces()->getInterfaces();
            $activeSessions = MikroTik::hotspot()->getActiveHosts();
        } catch (\Throwable $e) {
            Log::error('RouterOS: routeur injoignable depuis l\'écran d\'administration', [
                'error' => $e->getMessage(),
            ]);

            return [
                'routerOnline' => false,
                'routerConfigured' => true,
                'identity' => null,
                'resources' => [],
                'interfaces' => [],
                'activeSessions' => [],
            ];
        }

        return [
            'routerOnline' => true,
            'routerConfigured' => true,
            'identity' => $identity,
            'resources' => $resources,
            'interfaces' => $interfaces,
            'activeSessions' => $activeSessions,
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Routeur</flux:heading>

        <div class="flex gap-2">
            <flux:button size="sm" variant="ghost" icon="cog-6-tooth" wire:click="configure">
                Configurer
            </flux:button>
            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="$refresh">
                Actualiser
            </flux:button>
        </div>
    </div>

    @if (! $routerConfigured)
        <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">Aucun routeur configuré</flux:heading>
            <flux:text class="text-zinc-500">
                Le reste de l'administration (forfaits, comptes Hotspot, tableau de bord...) reste inaccessible tant
                qu'aucun routeur MikroTik n'a été enregistré ici.
            </flux:text>
            <div>
                <flux:button variant="primary" icon="cog-6-tooth" wire:click="configure">
                    Configurer un routeur
                </flux:button>
            </div>
        </div>
    @elseif (! $routerOnline)
        <div class="flex flex-col gap-1 rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-900 dark:bg-red-950">
            <flux:heading size="lg" class="text-red-700 dark:text-red-400">Routeur injoignable</flux:heading>
            <flux:text class="text-red-600 dark:text-red-400">
                Impossible de contacter le routeur MikroTik. Vérifiez qu'il est allumé, accessible sur le réseau et
                que les identifiants API sont corrects.
            </flux:text>
        </div>
    @else
        @php
            $cpuLoad = (int) ($resources['cpu-load'] ?? 0);
            $totalMemory = (int) ($resources['total-memory'] ?? 0);
            $freeMemory = (int) ($resources['free-memory'] ?? 0);
            $usedMemory = max($totalMemory - $freeMemory, 0);
            $memoryPercent = $totalMemory > 0 ? (int) round($usedMemory / $totalMemory * 100) : 0;
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500">Identité</flux:text>
                <flux:heading size="lg">{{ $identity }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">RouterOS {{ $resources['version'] ?? '—' }}</flux:text>
            </div>

            <div class="flex flex-col gap-1 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500">Disponibilité</flux:text>
                <flux:heading size="lg">{{ $resources['uptime'] ?? '—' }}</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ $resources['board-name'] ?? '—' }}</flux:text>
            </div>

            <div class="flex flex-col gap-2 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500">Charge CPU</flux:text>
                <flux:heading size="lg">{{ $cpuLoad }}%</flux:heading>
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="h-full rounded-full bg-[#0F9D8C]" style="width: {{ min($cpuLoad, 100) }}%"></div>
                </div>
            </div>

            <div class="flex flex-col gap-2 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500">Mémoire</flux:text>
                <flux:heading size="lg">{{ Number::fileSize($usedMemory, precision: 1) }} / {{ Number::fileSize($totalMemory, precision: 1) }}</flux:heading>
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="h-full rounded-full bg-[#0F9D8C]" style="width: {{ min($memoryPercent, 100) }}%"></div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Sessions Hotspot actives</flux:heading>

            @if (empty($activeSessions))
                <flux:text class="text-zinc-500">Aucune session active pour le moment.</flux:text>
            @else
                <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium text-zinc-500">Utilisateur</th>
                                <th class="px-4 py-3 text-start font-medium text-zinc-500">Adresse IP</th>
                                <th class="px-4 py-3 text-start font-medium text-zinc-500">Adresse MAC</th>
                                <th class="px-4 py-3 text-start font-medium text-zinc-500">Connecté depuis</th>
                                <th class="px-4 py-3 text-start font-medium text-zinc-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($activeSessions as $session)
                                <tr wire:key="session-{{ $session['.id'] ?? $session['user'] }}">
                                    <td class="px-4 py-3 font-mono">{{ $session['user'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-zinc-500">{{ $session['address'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-zinc-500">{{ $session['mac-address'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-zinc-500">{{ $session['uptime'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <flux:button
                                            size="sm"
                                            variant="danger"
                                            wire:click="kickSession('{{ $session['user'] ?? '' }}')"
                                            wire:confirm="Déconnecter cette session ?"
                                        >
                                            Déconnecter
                                        </flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">Interfaces réseau</flux:heading>

            @if (empty($interfaces))
                <flux:text class="text-zinc-500">Aucune interface remontée par le routeur.</flux:text>
            @else
                <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium text-zinc-500">Nom</th>
                                <th class="px-4 py-3 text-start font-medium text-zinc-500">Type</th>
                                <th class="px-4 py-3 text-start font-medium text-zinc-500">État</th>
                                <th class="px-4 py-3 text-start font-medium text-zinc-500">Adresse MAC</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($interfaces as $interface)
                                <tr wire:key="interface-{{ $interface['.id'] ?? $interface['name'] }}">
                                    <td class="px-4 py-3 font-medium">{{ $interface['name'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-zinc-500">{{ $interface['type'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if (($interface['disabled'] ?? 'false') === 'true')
                                            <flux:badge color="zinc" size="sm">Désactivée</flux:badge>
                                        @elseif (($interface['running'] ?? 'false') === 'true')
                                            <flux:badge color="lime" size="sm">Active</flux:badge>
                                        @else
                                            <flux:badge color="red" size="sm">Hors ligne</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 font-mono">{{ $interface['mac-address'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <flux:modal name="router-settings-form" class="max-w-lg">
        <form wire:submit="saveSettings" class="flex flex-col gap-6">
            <flux:heading size="lg">Configurer le routeur</flux:heading>

            <flux:field>
                <flux:label>Adresse (host)</flux:label>
                <flux:input wire:model="host" placeholder="192.168.88.1" />
                <flux:error name="host" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Port</flux:label>
                    <flux:input type="number" wire:model="port" min="1" max="65535" />
                    <flux:error name="port" />
                </flux:field>

                <flux:field>
                    <flux:label>Délai d'attente (secondes)</flux:label>
                    <flux:input type="number" wire:model="timeout" min="1" max="120" />
                    <flux:error name="timeout" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Utilisateur API</flux:label>
                <flux:input wire:model="username" />
                <flux:error name="username" />
            </flux:field>

            <flux:field>
                <flux:label>Mot de passe API</flux:label>
                <flux:input type="password" wire:model="password" viewable />
                <flux:error name="password" />
            </flux:field>

            <flux:checkbox wire:model="use_ssl" label="Connexion SSL (port API-SSL, 8729 par défaut)" />

            <div class="flex items-center justify-between gap-2">
                <flux:button type="button" size="sm" variant="filled" icon="signal" wire:click="testConnection">
                    Tester la connexion
                </flux:button>

                <div class="flex gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Annuler</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Enregistrer</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>

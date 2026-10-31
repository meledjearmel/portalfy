@props([
    'code',
    'hotspotContext' => null,
    'loginCredentials' => null,
    'shareUrl' => null,
    'zoneName' => null,
])

{{--
    Authentifie réellement l'appareil auprès de RouterOS : le navigateur du
    visiteur doit transmettre username/password à `link_login` (l'URL locale
    du routeur, capturée par CaptureHotspotContextAction), ce que ce serveur
    ne peut jamais faire à sa place puisqu'il n'est pas sur le réseau hotspot.

    Redirection de toute la page en GET plutôt qu'un formulaire posté dans
    une iframe cachée : le portail est servi en https et `link_login` en
    http, et les navigateurs bloquent sans bruit une iframe http dans une
    page https (contenu mixte), alors qu'ils laissent passer une navigation
    de premier niveau. RouterOS accepte ces paramètres en GET si le profil
    hotspot autorise `http-pap` (login-by).

    Sans contexte capturé (visiteur arrivé hors wifi), aucune tentative n'est
    possible : on l'explique à la place plutôt que de deviner une action.
--}}
@php
    $hotspotLoginUrl = null;

    if ($hotspotContext && $loginCredentials) {
        $hotspotLoginUrl = $hotspotContext['link_login']
            .(str_contains($hotspotContext['link_login'], '?') ? '&' : '?')
            .http_build_query(array_filter([
                'username' => $loginCredentials['username'],
                'password' => $loginCredentials['password'],
                'dst' => $hotspotContext['link_orig'],
            ]));
    }
@endphp

@if ($hotspotLoginUrl)
    <div
        data-hotspot-auto-login
        x-data="{ loginUrl: @js($hotspotLoginUrl) }"
        x-init="setTimeout(() => window.location.href = loginUrl, 800)"
    ></div>

    <flux:text class="glass-text text-xs opacity-70">
        Connexion automatique au wifi en cours...
    </flux:text>
@else
    <flux:text class="glass-text text-sm opacity-90">
        Connecte-toi au wifi{{ $zoneName ? " {$zoneName}" : '' }} puis reviens sur cette page pour une connexion
        automatique.
    </flux:text>
@endif

<div x-data="{ copied: false }" class="flex w-full flex-col gap-3">
    @if ($hotspotLoginUrl)
        <flux:button
            :href="$hotspotLoginUrl"
            variant="ghost"
            class="glass-button w-full"
        >
            Se connecter au WiFi
        </flux:button>
    @endif

    <div class="flex gap-3">
        <flux:button
            variant="ghost"
            class="glass-button flex-1"
            x-on:click="
                navigator.clipboard.writeText('{{ $code }}');
                copied = true;
                setTimeout(() => copied = false, 2000);
            "
        >
            <span x-show="!copied">Copier</span>
            <span x-show="copied" x-cloak>Copié !</span>
        </flux:button>

        @if ($shareUrl)
            <flux:button
                variant="ghost"
                class="glass-button flex-1"
                x-show="navigator.share"
                x-on:click="navigator.share({ title: 'Accès WiFi', text: 'Mon code WiFi : {{ $code }}', url: '{{ $shareUrl }}' })"
            >
                Partager
            </flux:button>
        @endif
    </div>
</div>

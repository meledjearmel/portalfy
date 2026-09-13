@props([
    'code',
    'hotspotContext' => null,
    'loginCredentials' => null,
    'shareUrl' => null,
    'zoneName' => null,
])

{{--
    Authentifie réellement l'appareil auprès de RouterOS : le navigateur du
    visiteur doit poster username/password sur `link_login` (l'URL locale du
    routeur, capturée par CaptureHotspotContextAction), ce que ce serveur ne
    peut jamais faire à sa place puisqu'il n'est pas sur le réseau hotspot.
    Sans contexte capturé (visiteur arrivé hors wifi), aucune tentative n'est
    possible : on l'explique à la place plutôt que de deviner une action.
--}}
@if ($hotspotContext && $loginCredentials)
    <div wire:ignore>
        <iframe name="hotspot-auto-login-frame" class="hidden" aria-hidden="true"></iframe>
        <form
            id="hotspot-auto-login-form"
            method="post"
            target="hotspot-auto-login-frame"
            action="{{ $hotspotContext['link_login'] }}"
        >
            <input type="hidden" name="username" value="{{ $loginCredentials['username'] }}">
            <input type="hidden" name="password" value="{{ $loginCredentials['password'] }}">
            @if ($hotspotContext['link_orig'])
                <input type="hidden" name="dst" value="{{ $hotspotContext['link_orig'] }}">
            @endif
        </form>
        <script>
            document.getElementById('hotspot-auto-login-form').submit();
        </script>
    </div>

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
    @if ($hotspotContext && $loginCredentials)
        <flux:button
            variant="ghost"
            class="glass-button w-full"
            x-on:click="document.getElementById('hotspot-auto-login-form').submit()"
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

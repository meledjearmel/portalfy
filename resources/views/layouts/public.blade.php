<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen text-[#0B2B26] antialiased">
        <svg width="0" height="0" style="position: absolute;">
            <defs>
                <filter id="glass-distortion" x="0%" y="0%" width="100%" height="100%">
                    <feTurbulence type="fractalNoise" baseFrequency="0.01 0.01" numOctaves="2" seed="92" result="noise" />
                    <feGaussianBlur in="noise" stdDeviation="2" result="blurred" />
                    <feDisplacementMap in="SourceGraphic" in2="blurred" scale="55" xChannelSelector="R" yChannelSelector="G" />
                </filter>
            </defs>
        </svg>

        <div
            class="page-background"
            style="background-image: linear-gradient(180deg, rgba(11, 43, 38, 0.55), rgba(11, 43, 38, 0.35)), url('{{ $wifiZoneSetting->background_url }}')"
        ></div>

        <div class="flex min-h-screen flex-col">
            <header class="glass-card m-4 px-4 py-3">
                <div class="mx-auto flex w-full max-w-5xl items-center justify-between gap-4 md:grid md:grid-cols-3">
                    <a href="{{ route('home') }}" wire:navigate class="flex min-w-0 shrink items-center gap-2 md:shrink-0 md:justify-self-start">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white/20">
                            <flux:icon name="wifi" class="glass-text size-4" />
                        </span>
                        <span class="glass-text truncate text-base font-bold">{{ $wifiZoneSetting->name }}</span>
                    </a>

                    <nav class="hidden items-center gap-1 justify-self-center rounded-full border border-white/30 p-1 md:flex">
                        @foreach ([
                            'home' => 'Accueil',
                            'packages.index' => 'Forfaits',
                            'hotspot-access.show' => "J'ai un code",
                        ] as $routeName => $label)
                            <a
                                href="{{ route($routeName) }}"
                                wire:navigate
                                class="glass-text rounded-full px-4 py-1.5 text-sm font-medium transition-colors {{ request()->routeIs($routeName) ? 'bg-white/25' : 'opacity-80 hover:opacity-100' }}"
                            >
                                {{ $label }}
                            </a>
                        @endforeach
                    </nav>

                    <div class="flex shrink-0 items-center gap-2 md:justify-self-end">
                        <flux:dropdown position="bottom" align="end" class="md:hidden">
                            <flux:button variant="ghost" size="sm" icon="bars-3" class="glass-button rounded-full!" aria-label="Menu" />

                            <flux:menu class="glass-card border-white/30! bg-white/15! p-2!">
                                @foreach ([
                                    'home' => 'Accueil',
                                    'packages.index' => 'Forfaits',
                                    'hotspot-access.show' => "J'ai un code",
                                ] as $routeName => $label)
                                    <flux:menu.item
                                        :href="route($routeName)"
                                        wire:navigate
                                        class="text-white! {{ request()->routeIs($routeName) ? 'bg-white/20!' : '' }} data-active:bg-white/20!"
                                    >
                                        {{ $label }}
                                    </flux:menu.item>
                                @endforeach
                            </flux:menu>
                        </flux:dropdown>

                        @php
                            $accountHref = auth('customer')->check() ? route('account.profile') : route('login');
                        @endphp

                        <flux:button
                            :href="$accountHref"
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            icon="user-circle"
                            class="glass-button flex! rounded-full! md:hidden!"
                            aria-label="Mon compte"
                        />

                        <flux:button
                            :href="$accountHref"
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            icon:trailing="user-circle"
                            class="glass-button hidden! rounded-full! md:inline-flex!"
                        >
                            Mon compte
                        </flux:button>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-4xl flex-1 px-4 py-10 sm:py-14">
                {{ $slot }}
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

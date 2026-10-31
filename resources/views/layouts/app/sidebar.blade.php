@php
    $navigation = [
        'Accueil' => [
            ['label' => "Vue d'ensemble", 'icon' => 'home', 'route' => 'admin.dashboard'],
        ],
        'Ventes' => [
            ['label' => 'Codes WiFi', 'icon' => 'ticket', 'route' => 'admin.hotspot-accounts.index'],
            ['label' => 'Paiements en ligne', 'icon' => 'banknotes', 'route' => 'admin.orders.index'],
            ['label' => 'Clients', 'icon' => 'users', 'route' => 'admin.customers.*', 'href' => 'admin.customers.index'],
        ],
        'Mon offre' => [
            ['label' => 'Forfaits et tarifs', 'icon' => 'tag', 'route' => 'admin.packages.index'],
            ['label' => 'Personnaliser le portail', 'icon' => 'paint-brush', 'route' => 'admin.settings.edit'],
        ],
        'Technique' => [
            ['label' => 'Connexion au routeur', 'icon' => 'server', 'route' => 'admin.router.index'],
            ['label' => 'Éléments supprimés', 'icon' => 'trash', 'route' => 'admin.trash.index'],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="console h-dvh overflow-hidden bg-[var(--bg)] text-[var(--fg)]" x-data="{ menuOpen: false }">
        <div class="flex h-full gap-3 p-3">
            <div
                class="fixed inset-0 z-40 bg-black/40 lg:hidden"
                x-show="menuOpen"
                x-cloak
                x-on:click="menuOpen = false"
            ></div>

            <aside
                class="console-no-scrollbar fixed inset-y-3 left-3 z-50 flex w-64 shrink-0 flex-col overflow-y-auto rounded-[1.6rem] bg-[var(--card)] px-5 pb-5 transition-transform lg:static lg:translate-x-0"
                :class="menuOpen ? 'translate-x-0' : '-translate-x-[120%] lg:translate-x-0'"
            >
                <div class="sticky top-0 z-20 -mx-5 flex items-center justify-between bg-[var(--card)] px-5 pt-5 pb-3">
                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-2.5 text-xl font-extrabold tracking-tight">
                        <span class="grid size-9 place-items-center rounded-full bg-[var(--brand)] text-[var(--on-brand)]">
                            <x-app-logo-icon class="size-5 fill-current" />
                        </span>
                        {{ config('app.name', 'PortalFi') }}
                    </a>
                    <button type="button" class="rounded-full p-1.5 lg:hidden" x-on:click="menuOpen = false" aria-label="Fermer le menu">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                @foreach ($navigation as $group => $items)
                    <nav class="mt-4 first-of-type:mt-5">
                        <p class="px-3 py-2 text-xs font-medium tracking-wider text-[var(--muted-fg)] uppercase">{{ $group }}</p>
                        <ul class="mt-1 space-y-0.5">
                            @foreach ($items as $item)
                                @php($isActive = request()->routeIs($item['route']))
                                <li>
                                    <a
                                        href="{{ route($item['href'] ?? $item['route']) }}"
                                        wire:navigate
                                        @class([
                                            'relative flex items-center gap-3 rounded-xl px-3 py-2 text-[15px] transition',
                                            'font-semibold text-[var(--fg)]' => $isActive,
                                            'text-[var(--muted-fg)] hover:text-[var(--fg)]' => ! $isActive,
                                        ])
                                        @if ($isActive) aria-current="page" @endif
                                    >
                                        @if ($isActive)
                                            <span class="absolute top-1/2 -left-5 h-8 w-1.5 -translate-y-1/2 rounded-r-full bg-[var(--brand)]"></span>
                                        @endif
                                        <flux:icon :name="$item['icon']" variant="outline" class="size-5 shrink-0" />
                                        <span class="truncate">{{ $item['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endforeach

                <div class="relative mt-8 shrink-0 overflow-hidden rounded-2xl bg-[var(--nav)] p-4 text-white">
                    <div aria-hidden="true" class="absolute -top-10 -right-10 size-36 rounded-full bg-[radial-gradient(circle,var(--brand),transparent_70%)] opacity-60"></div>
                    <p class="relative text-lg leading-tight font-bold">Le portail WiFi</p>
                    <p class="relative mt-1 text-xs text-[var(--nav-icon)]">Voir la page telle que vos clients la voient.</p>
                    <a
                        href="{{ route('home') }}"
                        target="_blank"
                        rel="noreferrer"
                        class="relative mt-4 flex items-center justify-center gap-2 rounded-full bg-[var(--brand)] py-2.5 text-sm font-semibold text-[var(--on-brand)]"
                    >
                        Ouvrir le portail
                        <flux:icon name="arrow-up-right" variant="micro" class="size-4" />
                    </a>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col gap-3">
                <header class="flex shrink-0 items-center gap-3 rounded-[1.6rem] bg-[var(--card)] px-3 py-3 sm:px-5">
                    <button
                        type="button"
                        class="grid size-11 shrink-0 place-items-center rounded-full bg-[var(--soft)] lg:hidden"
                        x-on:click="menuOpen = true"
                        aria-label="Ouvrir le menu"
                    >
                        <flux:icon name="bars-3" class="size-5" />
                    </button>

                    <p class="min-w-0 flex-1 truncate font-semibold">{{ $title ?? '' }}</p>

                    <div class="ml-auto flex items-center gap-2">
                        <button
                            type="button"
                            class="grid size-11 place-items-center rounded-full bg-[var(--soft)] transition hover:bg-[var(--soft-high)]"
                            x-on:click="$flux.dark = ! $flux.dark"
                            aria-label="Changer de thème"
                        >
                            <flux:icon name="moon" class="size-5 dark:hidden" />
                            <flux:icon name="sun" class="hidden size-5 dark:block" />
                        </button>

                        <flux:dropdown position="bottom" align="end">
                            <button type="button" class="flex items-center gap-3 rounded-full py-1 pr-1 text-left sm:pr-3" data-test="sidebar-menu-button">
                                <span class="grid size-11 place-items-center rounded-full bg-[var(--brand)] text-sm font-bold text-[var(--on-brand)]">
                                    {{ auth('web')->user()->initials() }}
                                </span>
                                <span class="hidden leading-tight md:block">
                                    <span class="block font-semibold">{{ auth('web')->user()->name }}</span>
                                    <span class="block text-xs text-[var(--muted-fg)]">{{ auth('web')->user()->email }}</span>
                                </span>
                            </button>

                            <flux:menu class="min-w-60">
                                <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>
                                    Mon compte
                                </flux:menu.item>
                                <flux:menu.separator />
                                <form method="POST" action="{{ route('admin.logout') }}" class="w-full">
                                    @csrf
                                    <flux:menu.item
                                        as="button"
                                        type="submit"
                                        icon="arrow-right-start-on-rectangle"
                                        class="w-full cursor-pointer"
                                        data-test="logout-button"
                                    >
                                        Se déconnecter
                                    </flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </header>

                <main class="console-no-scrollbar flex min-h-0 flex-1 flex-col overflow-y-auto overscroll-contain rounded-[1.6rem] bg-[color-mix(in_oklab,var(--soft)_55%,var(--bg))] p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

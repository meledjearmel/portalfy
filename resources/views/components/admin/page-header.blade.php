@props([
    'title',
    'description' => null,
])

<div class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0 max-w-2xl">
        <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-[var(--muted-fg)]">{{ $description }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>

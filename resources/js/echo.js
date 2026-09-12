import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

/**
 * Used by the order return screen (pages::orders.return) to show the
 * hotspot code the instant it's provisioned, via Livewire's PUBLIC echo:
 * listener (not echo-private:) — the channel is intentionally public, the
 * order reference itself acting as the shared secret, because the checkout
 * flow is anonymous (no account required). Do not switch this to a private
 * channel: Reverb/Pusher reject private-channel subscriptions from
 * unauthenticated guests before the custom authorization callback even
 * runs, which would break this flow entirely. wire:poll stays as a
 * fallback if the socket never connects (Reverb down, browser/network
 * blocking WebSockets).
 *
 * Skip initialization gracefully when unconfigured rather than crash the
 * whole bundle (e.g. a deploy that hasn't set VITE_REVERB_APP_KEY yet).
 */
if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}

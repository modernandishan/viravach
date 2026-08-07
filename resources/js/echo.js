import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

/**
 * Subscribe to a musonza per-conversation channel and run `onMessage` for
 * every MessageWasSent broadcast on it.
 *
 * Livewire only registers a component's echo listeners at mount, but the
 * chat surfaces learn conversation ids AFTER mount (drawers lazy-load on
 * open, thread pages switch conversations on click), so those surfaces
 * subscribe through this helper from Alpine instead. Deduped per page so
 * watchers may call it repeatedly with the same id; no unsubscribe needed
 * because the app never uses wire:navigate — subscriptions end with the
 * full page load.
 */
const chatConversationChannels = new Set();

window.listenToChatConversation = (conversationId, onMessage) => {
    if (!conversationId || chatConversationChannels.has(conversationId)) {
        return;
    }

    chatConversationChannels.add(conversationId);

    window.Echo.private(`mc-chat-conversation.${conversationId}`)
        .listen('.Musonza\\Chat\\Eventing\\MessageWasSent', onMessage);
};

/**
 * Scrolls every chat thread container to its newest message after each
 * Livewire commit (send, Echo-triggered receive, or poll fallback).
 *
 * A MutationObserver watching the container's children doesn't survive
 * Livewire's morph reliably (the container can be patched via wire:key
 * recreation or a full childList replace rather than an append, so the
 * observer's callback either never fires or fires on a now-detached node).
 * `morphed` is a Livewire lifecycle hook that runs after every commit
 * regardless of how the morph touched the DOM, so it's used here instead —
 * one global listener drives all four chat surfaces via a shared marker
 * attribute rather than each container wiring its own observer.
 */
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morphed', ({ el }) => {
        el.querySelectorAll('[data-chat-scroll]').forEach((container) => {
            container.scrollTop = container.scrollHeight;
        });
    });
});

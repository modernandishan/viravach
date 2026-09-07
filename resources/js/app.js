import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import tiptapEditor from './components/tiptap-editor.js';
import 'jalalidatepicker/dist/jalalidatepicker.min.css';
import 'jalalidatepicker/dist/jalalidatepicker.min.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('tiptapEditor', tiptapEditor);
});

// jalaliDatepicker delegates its listeners from document.body, so a single
// startWatch() keeps working for inputs Livewire adds later via DOM
// morphing — but it still needs to run again after a wire:navigate swap.
window.jalaliDatepicker?.startWatch({ autoHide: true, autoShow: true });
document.addEventListener('livewire:navigated', () => {
    window.jalaliDatepicker?.startWatch({ autoHide: true, autoShow: true });
});


/**
 * Sitewide flash / validation notifications.
 *
 * resources/views/partials/flash-alerts.blade.php pushes `{icon, title}`
 * objects onto `window.viravachFlashQueue` and then calls
 * `window.viravachFlashDrain()`. The queue exists because that partial's Alpine
 * `x-init` can run before this module does: Livewire's classic <script> at the
 * end of <body> boots Alpine while this bundle is a deferred module in <head>.
 * Whichever side runs first, nothing is lost.
 */
const flashToast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    showCloseButton: true,
    timer: 6000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

let flashDraining = false;

window.viravachFlashQueue ??= [];

window.viravachFlashDrain = async () => {
    // SweetAlert2 shows one popup at a time, so queued messages are awaited in
    // turn instead of overwriting each other.
    if (flashDraining) {
        return;
    }

    flashDraining = true;

    try {
        while (window.viravachFlashQueue.length > 0) {
            const { icon = 'info', title = '' } = window.viravachFlashQueue.shift() ?? {};

            await flashToast.fire({
                icon,
                title,
                // Metronic keeps the active theme on <html data-bs-theme>, which
                // its own toggle updates in place; mirroring it here keeps the
                // toast readable in dark mode.
                theme: document.documentElement.getAttribute('data-bs-theme') === 'dark'
                    ? 'dark'
                    : 'light',
            });
        }
    } finally {
        flashDraining = false;
    }
};

// Anything the partial queued before this bundle executed.
window.viravachFlashDrain();


/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

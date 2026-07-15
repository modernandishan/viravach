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


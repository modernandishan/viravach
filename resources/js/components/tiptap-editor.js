import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';

function readXsrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : null;
}

// Editor instances are deliberately kept OUTSIDE Alpine's reactive proxy:
// wrapping a Tiptap/ProseMirror editor in a Proxy corrupts its internal
// state comparisons and produces "Applying a mismatched transaction"
// errors. Keyed by the component's root element so each instance resolves
// its own editor, and garbage-collected together with the DOM node.
const editors = new WeakMap();

export default function tiptapEditor(content, dir = 'ltr', placeholder = '') {
    return {
        content,
        updatedAt: 0,
        dir,

        init() {
            const editor = new Editor({
                element: this.$refs.element,
                extensions: [
                    // StarterKit already ships the Link extension in Tiptap
                    // v3 — configure it here instead of registering the
                    // standalone extension a second time.
                    StarterKit.configure({
                        link: { openOnClick: false },
                    }),
                    Image,
                    Placeholder.configure({ placeholder }),
                ],
                content: this.content ?? '',
                editorProps: {
                    attributes: { dir: this.dir },
                },
                onUpdate: ({ editor }) => {
                    this.content = editor.getHTML();
                },
                onTransaction: () => {
                    this.updatedAt++;
                },
            });

            editors.set(this.$root, editor);
        },

        // Alpine calls this automatically when the element is removed from
        // the DOM (Livewire morphs, wire:navigate page swaps, ...).
        destroy() {
            editors.get(this.$root)?.destroy();
            editors.delete(this.$root);
        },

        /**
         * Always re-resolve the current instance instead of closing over a
         * stale reference; returns null once the editor is gone.
         */
        getEditor() {
            const editor = editors.get(this.$root);

            return editor && ! editor.isDestroyed ? editor : null;
        },

        isActive(name, attrs = {}) {
            this.updatedAt;

            return this.getEditor()?.isActive(name, attrs) ?? false;
        },

        toggleBold() {
            this.getEditor()?.chain().focus().toggleBold().run();
        },

        toggleItalic() {
            this.getEditor()?.chain().focus().toggleItalic().run();
        },

        toggleHeading(level) {
            this.getEditor()?.chain().focus().toggleHeading({ level }).run();
        },

        toggleBulletList() {
            this.getEditor()?.chain().focus().toggleBulletList().run();
        },

        toggleOrderedList() {
            this.getEditor()?.chain().focus().toggleOrderedList().run();
        },

        toggleBlockquote() {
            this.getEditor()?.chain().focus().toggleBlockquote().run();
        },

        setLink() {
            const editor = this.getEditor();

            if (! editor) {
                return;
            }

            const previousUrl = editor.getAttributes('link').href;
            const url = window.prompt('URL', previousUrl ?? '');

            if (url === null) {
                return;
            }

            if (url === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();

                return;
            }

            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        },

        async uploadImage(event) {
            const input = event.target;
            const file = input.files[0];

            if (! file) {
                return;
            }

            const formData = new FormData();
            formData.append('image', file);

            const response = await fetch('/editor/upload', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': readXsrfToken(),
                },
                credentials: 'same-origin',
                body: formData,
            });

            input.value = '';

            if (! response.ok) {
                return;
            }

            const { url } = await response.json();

            // The upload is async: by the time it resolves, Livewire may
            // have morphed the page and replaced/destroyed the editor.
            // Re-resolve the live instance and bail out if it is gone so we
            // never dispatch a transaction against a stale editor state.
            const editor = this.getEditor();

            if (! editor) {
                return;
            }

            editor.chain().focus().setImage({ src: url }).run();
        },

        undo() {
            this.getEditor()?.chain().focus().undo().run();
        },

        redo() {
            this.getEditor()?.chain().focus().redo().run();
        },
    };
}

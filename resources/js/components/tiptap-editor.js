import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';

function readXsrfToken() {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : null;
}

export default function tiptapEditor(content, dir = 'ltr', placeholder = '') {
    return {
        content,
        editor: null,
        updatedAt: 0,
        dir,

        init() {
            this.editor = new Editor({
                element: this.$refs.element,
                extensions: [
                    StarterKit,
                    Image,
                    Link.configure({ openOnClick: false }),
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
        },

        isActive(name, attrs = {}) {
            this.updatedAt;

            return this.editor?.isActive(name, attrs) ?? false;
        },

        toggleBold() {
            this.editor.chain().focus().toggleBold().run();
        },

        toggleItalic() {
            this.editor.chain().focus().toggleItalic().run();
        },

        toggleHeading(level) {
            this.editor.chain().focus().toggleHeading({ level }).run();
        },

        toggleBulletList() {
            this.editor.chain().focus().toggleBulletList().run();
        },

        toggleOrderedList() {
            this.editor.chain().focus().toggleOrderedList().run();
        },

        toggleBlockquote() {
            this.editor.chain().focus().toggleBlockquote().run();
        },

        setLink() {
            const previousUrl = this.editor.getAttributes('link').href;
            const url = window.prompt('URL', previousUrl ?? '');

            if (url === null) {
                return;
            }

            if (url === '') {
                this.editor.chain().focus().extendMarkRange('link').unsetLink().run();

                return;
            }

            this.editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        },

        async uploadImage(event) {
            const file = event.target.files[0];

            if (!file) {
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

            event.target.value = '';

            if (!response.ok) {
                return;
            }

            const { url } = await response.json();

            this.editor.chain().focus().setImage({ src: url }).run();
        },

        undo() {
            this.editor.chain().focus().undo().run();
        },

        redo() {
            this.editor.chain().focus().redo().run();
        },

        destroy() {
            this.editor?.destroy();
        },
    };
}

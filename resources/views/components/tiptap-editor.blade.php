@props([
    'dir' => null,
    'placeholder' => '',
])

@php
    $dir ??= \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() === 'rtl' ? 'rtl' : 'ltr';
@endphp

<div
    {{ $attributes->whereDoesntStartWith('wire:model') }}
    wire:ignore
    x-data="tiptapEditor(@entangle($attributes->wire('model')), @js($dir), @js($placeholder))"
    dir="{{ $dir }}"
>
    <style>
        .tiptap img,
        .tiptap video,
        .tiptap iframe{
            width: 100%;
        }
    </style>
    <div class="btn-toolbar border border-bottom-0 rounded-top p-2 bg-light d-flex flex-wrap gap-1" role="toolbar">
        <button type="button" class="btn btn-sm btn-icon fw-bold" :class="isActive('bold') ? 'btn-light-primary' : 'btn-light'" @click="toggleBold" title="Bold">B</button>
        <button type="button" class="btn btn-sm btn-icon fst-italic" :class="isActive('italic') ? 'btn-light-primary' : 'btn-light'" @click="toggleItalic" title="Italic">I</button>

        <div class="vr mx-1"></div>

        <button type="button" class="btn btn-sm btn-icon" :class="isActive('heading', { level: 2 }) ? 'btn-light-primary' : 'btn-light'" @click="toggleHeading(2)">H2</button>
        <button type="button" class="btn btn-sm btn-icon" :class="isActive('heading', { level: 3 }) ? 'btn-light-primary' : 'btn-light'" @click="toggleHeading(3)">H3</button>
        <button type="button" class="btn btn-sm btn-icon" :class="isActive('heading', { level: 4 }) ? 'btn-light-primary' : 'btn-light'" @click="toggleHeading(4)">H4</button>

        <div class="vr mx-1"></div>

        <button type="button" class="btn btn-sm btn-icon" :class="isActive('bulletList') ? 'btn-light-primary' : 'btn-light'" @click="toggleBulletList" title="Bullet list">&bull;&bull;&bull;</button>
        <button type="button" class="btn btn-sm btn-icon" :class="isActive('orderedList') ? 'btn-light-primary' : 'btn-light'" @click="toggleOrderedList" title="Ordered list">1.2.3.</button>
        <button type="button" class="btn btn-sm btn-icon" :class="isActive('blockquote') ? 'btn-light-primary' : 'btn-light'" @click="toggleBlockquote" title="Blockquote">&rdquo;</button>

        <div class="vr mx-1"></div>

        <button type="button" class="btn btn-sm btn-icon" :class="isActive('link') ? 'btn-light-primary' : 'btn-light'" @click="setLink" title="Link">&#128279;</button>
        <button type="button" class="btn btn-sm btn-icon btn-light" @click="$refs.imageInput.click()" title="Image">&#128247;</button>

        <div class="vr mx-1"></div>

        <button type="button" class="btn btn-sm btn-icon btn-light" @click="undo" title="Undo">&#8630;</button>
        <button type="button" class="btn btn-sm btn-icon btn-light" @click="redo" title="Redo">&#8631;</button>

        <input type="file" accept="image/png,image/jpeg,image/webp" class="d-none" x-ref="imageInput" @change="uploadImage($event)">
    </div>

    <div x-ref="element" class="form-control form-control-solid rounded-top-0" style="min-height: 220px;"></div>
</div>

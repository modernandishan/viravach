{{--
    Recursive, collapsible category picker for the company wizard.

    Expand/collapse is pure client-side Alpine state; selection stays bound
    to the Livewire `categoryIds` property exactly like before. While five
    categories are already selected, unchecked boxes render disabled (checked
    ones stay clickable so the user can deselect). Indentation
    uses the logical padding-inline-start property so it flips correctly
    between RTL (fa/ar) and LTR (en/ru/tr) locales.
--}}
@props(['nodes', 'expandedIds' => [], 'selectedIds' => [], 'depth' => 0])

@php
    $isRtl = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getCurrentLocaleDirection() === 'rtl';
    $closedRotation = $isRtl ? '90deg' : '-90deg';
@endphp

@foreach ($nodes as $node)
    @php $hasChildren = $node->children->isNotEmpty(); @endphp
    <div wire:key="category-node-{{ $node->id }}"
         x-data="{ open: @js(in_array($node->id, $expandedIds)) }">
        <div class="d-flex align-items-center py-1" style="padding-inline-start: {{ $depth * 1.75 }}rem">
            @if ($hasChildren)
                <button type="button"
                        class="btn btn-sm btn-icon btn-color-gray-500 btn-active-color-primary w-25px h-25px me-1"
                        x-on:click="open = ! open"
                        :aria-expanded="open">
                    <i class="ki-duotone ki-down fs-4"
                       style="transition: transform 0.2s ease"
                       :style="open ? '' : 'transform: rotate({{ $closedRotation }})'"></i>
                </button>
            @else
                <span class="w-25px me-1 flex-shrink-0"></span>
            @endif

            <label class="form-check form-check-sm form-check-custom form-check-solid cursor-pointer">
                <input class="form-check-input" type="checkbox" wire:model="categoryIds" value="{{ $node->id }}"
                       @if (count($selectedIds) >= 5 && ! in_array($node->id, $selectedIds)) disabled @endif>
                <span class="form-check-label fw-semibold text-gray-800">{{ $node->title }}</span>
            </label>
        </div>

        @if ($hasChildren)
            <div x-show="open">
                <x-company-elements.category-tree-select :nodes="$node->children" :expanded-ids="$expandedIds" :selected-ids="$selectedIds" :depth="$depth + 1" />
            </div>
        @endif
    </div>
@endforeach

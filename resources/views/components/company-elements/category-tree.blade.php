@props(['nodes', 'activeIds' => []])

@foreach ($nodes as $node)
    @php
        $hasChildren = $node->children->isNotEmpty();
        $isOnPath = in_array($node->id, $activeIds, true);
        $isCurrent = $isOnPath && $node->id === ($activeIds[array_key_last($activeIds)] ?? null);
        $isRoot = $node->parent_id === null;
        $levelClass = $isRoot ? 'fw-bold text-gray-900' : 'fw-semibold text-gray-600 fs-7';
    @endphp
    <div class="menu-item {{ $hasChildren ? 'menu-accordion' : '' }} {{ $isOnPath && $hasChildren ? 'show' : '' }}"
         @if ($hasChildren)
             data-kt-menu-trigger="click"
             x-data="{ open: {{ $isOnPath ? 'true' : 'false' }} }"
             :class="{ show: open }"
         @endif>
        <a href="{{ route('companies.category', ['slug' => $node->slug]) }}"
           class="menu-link {{ $levelClass }} {{ $isCurrent ? 'active' : '' }}">
            <span class="menu-bullet">
                <span class="bullet bullet-dot"></span>
            </span>
            <span class="menu-title">{{ $node->title }}</span>
            @if ($hasChildren)
                <span class="menu-arrow" @click.stop.prevent="open = !open"></span>
            @endif
        </a>

        @if ($hasChildren)
            <div class="menu-sub menu-sub-accordion">
                <x-company-elements.category-tree :nodes="$node->children" :active-ids="$activeIds" />
            </div>
        @endif
    </div>
@endforeach

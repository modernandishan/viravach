@props(['category', 'count' => 0])

<a href="{{ route('companies.category', ['slug' => $category->slug]) }}"
   class="card border-hover-primary h-100 text-center">
    <div class="card-body d-flex flex-column align-items-center py-8">
        <div class="symbol symbol-60px mb-4">
            @if ($category->hasMedia('logo'))
                <img src="{{ $category->getFirstMediaUrl('logo', 'webp') }}" alt="{{ $category->title }}" class="object-fit-contain">
            @else
                <span class="symbol-label bg-light-primary text-primary fs-2x">
                    <i class="ki-duotone ki-category fs-2x">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                    </i>
                </span>
            @endif
        </div>

        <div class="fs-5 fw-bold text-gray-900 mb-1">{{ $category->title }}</div>
        <div class="text-muted fs-7">{{ __('home.categories_count', ['count' => number_format($count)]) }}</div>
    </div>
</a>

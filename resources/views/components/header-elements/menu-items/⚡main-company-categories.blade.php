<?php

use App\Models\CompanyCategory;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function categories(): Collection
    {
        return CompanyCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
};
?>

<div class="col-lg-8 mb-3 mb-lg-0 py-3 px-3 py-lg-6 px-lg-6">
    <!--begin:Row-->
    <div class="row">

        @foreach ($this->categories as $category)
            <!--begin:Col-->
            <div class="col-lg-6 mb-3">
                <!--begin:Menu item-->
                <div class="menu-item p-0 m-0">
                    <!--begin:Menu link-->
                    <a href="{{route('companies.category', ['slug' => $category->slug])}}" class="menu-link active">
                        <span
                            class="menu-custom-icon d-flex flex-center flex-shrink-0 rounded w-40px h-40px me-3">
                            @if ($category->hasMedia('logo'))
                                <img src="{{ $category->getFirstMediaUrl('logo', 'webp') }}" alt="{{ $category->title }}" class="w-100 h-100 rounded object-fit-cover" />
                            @else
                                <i class="ki-duotone ki-element-11 text-primary fs-1">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            @endif
                        </span>
                        <span class="d-flex flex-column">
                            <span
                                class="fs-6 fw-bold text-gray-800">{{ $category->title }}</span>
                            {{--<span
                                class="fs-7 fw-semibold text-muted">{!! $category->description !!}</span>--}}
                        </span>
                    </a>
                    <!--end:Menu link-->
                </div>
                <!--end:Menu item-->
            </div>
            <!--end:Col-->
        @endforeach

    </div>
    <!--end:Row-->
    <div class="separator separator-dashed mx-5 my-5"></div>
    <!--begin:صفحه فرود-->
    <div class="d-flex flex-stack flex-wrap flex-lg-nowrap gap-2 mx-5">
        <div class="d-flex flex-column me-5">
            <div class="fs-6 fw-bold text-gray-800">برترین های صادرات</div>
            <div class="fs-7 fw-semibold text-muted">
                کسب و کار ها و شرکت ها با بیشترین امتیاز صادرات
            </div>
        </div>
        <a href="{{route('pricing')}}" class="btn btn-sm btn-primary fw-bold">مشاهده</a>
    </div>
    <!--end:صفحه فرود-->
</div>

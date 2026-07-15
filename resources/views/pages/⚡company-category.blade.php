<?php

use App\Models\CompanyCategory;
use Livewire\Component;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

new class extends Component {
    public CompanyCategory $category;

    public function mount(string $slug): void
    {
        $this->category = CompanyCategory::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    #[Computed]
    public function subCategories(): Collection
    {
        return $this->category->children()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
};
?>
<div class="content flex-row-fluid">

    <div class="container">
        <div class="card">
            <!--begin::کارت body-->
            <div class="card-body">
                <!--begin::Nav-->
                <ul class="nav mx-auto flex-shrink-0 flex-center flex-wrap border-transparent fs-6 fw-bold">
                    <!--begin::Nav item-->
                    @foreach ($this->subCategories as $subCategory)
                        <li class="nav-item">
                            <a class="btn btn-active-light-primary fw-bolder nav-link btn-color-gray-700 px-3 px-lg-8 mx-1 text-uppercase"
                               href="{{ route('companies.category', ['slug' => $subCategory->slug]) }}">
                                {{ $subCategory->title }}
                            </a>
                        </li>
                    @endforeach
                    <!--end::Nav item-->
                </ul>
                <!--end::Nav-->
            </div>
            <!--end::کارت body-->
        </div>

        <div class="row g-6 g-xl-9 my-4">
            <div class="col-md-6 col-xl-4">
                <!--begin::کارت-->
                <a href="" class="card border-hover-primary">
                    <!--begin::کارت header-->
                    <div class="card-header border-0 pt-9">
                        <!--begin::کارت Title-->
                        <div class="card-title m-0">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-50px w-50px bg-light">
                                <img src="" alt="image" class="p-3">
                            </div>
                            <!--end::Avatar-->
                        </div>
                        <!--end::Car Title-->
                        <!--begin::کارت toolbar-->
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fw-bold me-auto px-4 py-3">درحال پردازش</span>
                        </div>
                        <!--end::کارت toolbar-->
                    </div>
                    <!--end:: کارت header-->
                    <!--begin:: کارت body-->
                    <div class="card-body p-9">
                        <!--begin::نام-->
                        <div class="fs-3 fw-bold text-gray-900">برنامه تناسب اندام</div>
                        <!--end::نام-->
                        <!--begin::توضیحات-->
                        <p class="text-gray-500 fw-semibold fs-5 mt-1 mb-7">برنامه برای بهره وری منابع انسانی</p>
                        <!--end::توضیحات-->
                        <!--begin::Info-->
                        <div class="d-flex flex-wrap mb-5">
                            <!--begin::Due-->
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-7 mb-3">
                                <div class="fs-6 text-gray-800 fw-bold">Nov 10, 2024</div>
                                <div class="fw-semibold text-gray-500">سررسید</div>
                            </div>
                            <!--end::Due-->
                            <!--begin::بودجه-->
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 mb-3">
                                <div class="fs-6 text-gray-800 fw-bold">$284,900.00</div>
                                <div class="fw-semibold text-gray-500">بودجه</div>
                            </div>
                            <!--end::بودجه-->
                        </div>
                        <!--end::Info-->
                        <!--begin::پردازش-->
                        <div class="h-4px w-100 bg-light mb-5" data-bs-toggle="tooltip" aria-label="This project 50% completed" data-bs-original-title="This project 50% completed" data-kt-initialized="1">
                            <div class="bg-primary rounded h-4px" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <!--end::پردازش-->
                        <!--begin::users-->
                        <div class="symbol-group symbol-hover">
                            <!--begin::user-->
                            <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" aria-label="مرادی نیا" data-bs-original-title="مرادی نیا" data-kt-initialized="1">
                                <img alt="Pic" src="">
                            </div>
                            <!--begin::user-->
                            <!--begin::user-->
                            <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" aria-label="Rudy Stone" data-bs-original-title="Rudy Stone" data-kt-initialized="1">
                                <img alt="Pic" src="">
                            </div>
                            <!--begin::user-->
                            <!--begin::user-->
                            <div class="symbol symbol-35px symbol-circle" data-bs-toggle="tooltip" data-bs-original-title="سوسن موسوی" data-kt-initialized="1">
                                <span class="symbol-label bg-primary text-inverse-primary fw-bold">S</span>
                            </div>
                            <!--begin::user-->
                        </div>
                        <!--end::users-->
                    </div>
                    <!--end:: کارت body-->
                </a>
                <!--end::کارت-->
            </div>
        </div>

        <div class="d-flex flex-stack flex-wrap pt-10">
            <div class="fs-6 fw-semibold text-gray-700">صفحات 1 از 10</div>
            <!--begin::صفحات-->
            <ul class="pagination">
                <li class="page-item previous">
                    <a href="#" class="page-link">
                        <i class="previous"></i>
                    </a>
                </li>
                <li class="page-item active">
                    <a href="#" class="page-link">1</a>
                </li>
                <li class="page-item">
                    <a href="#" class="page-link">2</a>
                </li>
                <li class="page-item">
                    <a href="#" class="page-link">3</a>
                </li>
                <li class="page-item">
                    <a href="#" class="page-link">4</a>
                </li>
                <li class="page-item">
                    <a href="#" class="page-link">5</a>
                </li>
                <li class="page-item">
                    <a href="#" class="page-link">6</a>
                </li>
                <li class="page-item next">
                    <a href="#" class="page-link">
                        <i class="next"></i>
                    </a>
                </li>
            </ul>
            <!--end::صفحات-->
        </div>
    </div>


</div>



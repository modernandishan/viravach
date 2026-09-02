<?php

// routes/breadcrumbs.php

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\CompanyPublication;
use App\Models\Country;
use App\Models\State;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as Trail;

// خانه
Breadcrumbs::for('home', function (Trail $trail) {
    $trail->push(__('breadcrumbs.home'), route('home'));
});

// دسته‌بندی شرکت‌ها
Breadcrumbs::for('companies.category', function (Trail $trail, string $slug) {
    $trail->parent('home');

    $category = CompanyCategory::query()
        ->where('slug', $slug)
        ->where('is_active', true)
        ->firstOrFail();

    foreach ($category->ancestorsAndSelf()->orderBy('depth')->get() as $ancestor) {
        $trail->push($ancestor->title, route('companies.category', ['slug' => $ancestor->slug]));
    }
});

// کشورها (اینکس جغرافیایی)
Breadcrumbs::for('companies.countries', function (Trail $trail) {
    $trail->parent('home');

    $trail->push(__('breadcrumbs.countries'), route('companies.countries'));
});

// دایرکتوری کشور
Breadcrumbs::for('companies.country', function (Trail $trail, string $slug) {
    $trail->parent('companies.countries');

    $country = Country::query()
        ->where('slug', $slug)
        ->where('is_active', true)
        ->firstOrFail();

    $trail->push($country->name, route('companies.country', ['country' => $country->slug]));
});

// استان‌های کشور
Breadcrumbs::for('companies.state', function (Trail $trail, string $countrySlug, string $stateSlug) {
    $trail->parent('companies.country', $countrySlug);

    $state = State::query()
        ->where('slug', $stateSlug)
        ->where('is_active', true)
        ->whereHas('country', fn ($query) => $query->where('slug', $countrySlug))
        ->firstOrFail();

    $trail->push($state->name, route('companies.state', ['country' => $countrySlug, 'state' => $state->slug]));
});

// پروفایل عمومی شرکت (از روی اسنپ‌شات منتشرشده)
Breadcrumbs::for('companies.show', function (Trail $trail, string $slug) {
    $trail->parent('home');

    $publication = CompanyPublication::query()
        ->active()
        ->where('slug', $slug)
        ->firstOrFail();

    if ($category = $publication->categories()->first()) {
        $trail->push($category->title, route('companies.category', ['slug' => $category->slug]));
    }

    $trail->push($publication->name, route('companies.show', ['slug' => $slug]));
});

// قوانین و مقررات
Breadcrumbs::for('terms-and-conditions', function (Trail $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.terms_and_conditions'), route('terms-and-conditions'));
});

// --- احراز هویت ---

Breadcrumbs::for('auth.sign-in', function (Trail $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.sign_in'), route('auth.sign-in'));
});

Breadcrumbs::for('auth.secure-login', function (Trail $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.secure_login'), route('auth.secure-login'));
});

Breadcrumbs::for('auth.sign-up', function (Trail $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.sign_up'), route('auth.sign-up'));
});

Breadcrumbs::for('auth.reset-password', function (Trail $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.reset_password'), route('auth.reset-password'));
});

// --- داشبورد ---

Breadcrumbs::for('dashboard', function (Trail $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.dashboard'), route('dashboard'));
});

Breadcrumbs::for('profile', function (Trail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('breadcrumbs.profile'), route('profile'));
});

Breadcrumbs::for('settings', function (Trail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('breadcrumbs.settings'), route('settings'));
});

Breadcrumbs::for('my-companies', function (Trail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('breadcrumbs.my_companies'), route('my-companies'));
});

Breadcrumbs::for('create.company', function (Trail $trail) {
    $trail->parent('my-companies');
    $trail->push(__('breadcrumbs.create_company'), route('create.company'));
});

Breadcrumbs::for('edit.company', function (Trail $trail, Company $company) {
    $trail->parent('my-companies');
    $trail->push($company->name, route('edit.company', $company));
});

Breadcrumbs::for('subscriptions', function (Trail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('breadcrumbs.subscriptions'), route('subscriptions'));
});

Breadcrumbs::for('payments', function (Trail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('breadcrumbs.payments'), route('payments'));
});

Breadcrumbs::for('company-views', function (Trail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('breadcrumbs.company_views'), route('company-views'));
});

Breadcrumbs::for('pricing', function (Trail $trail) {
    $trail->parent('home');
    $trail->push(__('breadcrumbs.pricing'), route('pricing'));
});

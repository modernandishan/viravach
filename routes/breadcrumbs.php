<?php
// routes/breadcrumbs.php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as Trail;

// خانه
Breadcrumbs::for('home', function (Trail $trail) {
    $trail->push(__('breadcrumbs.home'), route('home'));
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

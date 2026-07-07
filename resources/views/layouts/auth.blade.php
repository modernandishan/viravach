<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ LaravelLocalization::getCurrentLocaleDirection() }}">
<!--begin::Head-->
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>

    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta property="og:locale" content="en_US"/>
    <meta property="og:type" content="article"/>
    <meta property="og:title" content=""/>
    <meta property="og:url" content="https://viravach.com"/>
    <meta property="og:site_name" content="Viravach"/>
    <link rel="canonical" href=""/>

    <link rel="shortcut icon" href="{{asset('favicon.ico')}}"/>

    <title>{{ $title ?? config('app.name') }}</title>

    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{asset('theme/1/plugins/global/plugins.bundle.rtl.css')}}" rel="stylesheet" type="text/css"/>
    <link href="{{asset('theme/1/css/style.bundle.rtl.css')}}" rel="stylesheet" type="text/css"/>
    <!--end::Global Stylesheets Bundle-->

    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }
    </script>

    @livewireStyles
</head>
<!--end::Head-->
<!--begin::Body-->
<body id="kt_body" class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">
<!--begin::Theme mode setup on page load-->
<script>
    var defaultThemeMode = "light";
    var themeMode;
    if (document.documentElement) {
        if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
            themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
        } else {
            if (localStorage.getItem("data-bs-theme") !== null) {
                themeMode = localStorage.getItem("data-bs-theme");
            } else {
                themeMode = defaultThemeMode;
            }
        }
        if (themeMode === "system") {
            themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        }
        document.documentElement.setAttribute("data-bs-theme", themeMode);
    }
</script>
<!--end::Theme mode setup on page load-->
<!--begin::اصلی-->
<!--begin::Root-->
<div class="d-flex flex-column flex-root">
    <!--begin::Page bg image-->
    <style>
        body {
            background-image: url('{{asset('theme/1/media/auth/bg4.jpg')}}');
        }

        [data-bs-theme="dark"] body {
            background-image: url('{{asset('theme/1/media/auth/bg4-dark.jpg')}}');
        }
    </style>
    <!--end::Page bg image-->
    <!--begin::احراز هویت - ورود -->
    {{ $slot }}
    <!--end::احراز هویت - ورود-->
</div>
<!--end::Root-->
<!--end::اصلی-->
<!--begin::Javascript-->
<script>var hostUrl = "theme/1/";</script>
<!--begin::Global Javascript Bundle(mandatory for all pages)-->
<script src="{{asset('theme/1/plugins/global/plugins.bundle.js')}}"></script>
<script src="{{asset('theme/1/js/scripts.bundle.js')}}"></script>
<!--end::Global Javascript Bundle-->
<!--begin::سفارشی Javascript(used for this page only)-->
<script src="{{asset('theme/1/js/custom/authentication/sign-in/general.js')}}"></script>
<!--end::سفارشی Javascript-->
<!--end::Javascript-->

@livewireScripts
</body>
<!--end::Body-->
</html>

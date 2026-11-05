@props(['title', 'subtitle' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#3D12C1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} · Admin Console · {{ config('app.name') }}</title>

        <link rel="icon" href="{{ \App\Support\BrandAsset::url('UC Run Logo.PNG') }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ \App\Support\BrandAsset::url('UC Run Logo.PNG') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/admin.js'])
    </head>
    <body class="min-h-dvh">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:text-body-sm focus:font-semibold focus:text-white">
            Lewati ke konten utama
        </a>

        <x-admin.sidebar />

        <div class="lg:pl-64">
            <div class="mx-auto w-full max-w-page px-4 pb-32 sm:px-6 lg:px-10 lg:pb-12">
                <x-admin.header :title="$title" :subtitle="$subtitle" />

                <main id="main-content">
                    <x-admin.flash />

                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-admin.bottom-nav />
        <x-admin.modal />
    </body>
</html>

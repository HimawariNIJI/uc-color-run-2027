@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#3D12C1">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $title }} · Admin Console · {{ config('app.name') }}</title>

        <link rel="icon" href="{{ \App\Support\BrandAsset::url('UC Run Logo.PNG') }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ \App\Support\BrandAsset::url('UC Run Logo.PNG') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/admin.js'])
    </head>
    <body class="min-h-dvh">
        <main id="main-content" class="relative isolate flex min-h-dvh items-center justify-center overflow-hidden px-4 pb-32 pt-10 sm:pb-40 sm:pt-16">
            {{-- Static colour-run landscape behind the sign-in card --}}
            <x-brand-image file="2@3x.png" eager class="absolute -left-24 top-6 -z-10 w-64 opacity-60 sm:w-96" />
            <x-brand-image file="3@3x.png" eager class="absolute -right-24 top-28 -z-10 w-64 opacity-60 sm:w-96" />
            <x-brand-image file="Asset 16@3x.png" eager class="absolute -bottom-2 -left-[10%] -z-10 w-[90%] sm:w-[50%]" />
            <x-brand-image file="Asset 17@3x.png" eager class="absolute -bottom-3 left-1/2 -z-10 w-[180%] -translate-x-1/2 sm:w-[110%]" />
            <x-brand-image file="Asset 10@3x.png" eager class="absolute bottom-0 left-[4%] -z-10 w-10 sm:w-16" />
            <x-brand-image file="Asset 12@3x.png" eager class="absolute bottom-0 right-[5%] -z-10 w-12 sm:w-20" />

            {{ $slot }}
        </main>
    </body>
</html>

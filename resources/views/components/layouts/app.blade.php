@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#713BDB">
        <meta name="description" content="{{ $description ?? 'Platform pendaftaran resmi UC Color Run 2027 oleh Universitas Ciputra. Pilih kategori 3K, 5K, atau 10K dan amankan slotmu sekarang.' }}">

        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-dvh flex-col">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:text-body-sm focus:font-semibold focus:text-white">
            Lewati ke konten utama
        </a>

        <x-site-header />

        <main id="main-content" class="flex-1">
            <x-flash />

            {{ $slot }}
        </main>

        <x-site-footer />
    </body>
</html>

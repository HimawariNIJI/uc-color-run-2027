@props(['title' => null, 'description' => null, 'decorated' => true])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#3D12C1">
        <meta name="description" content="{{ $description ?? 'Platform pendaftaran resmi UC Color Run 2027 oleh Universitas Ciputra. Pilih kategori 3K, 5K, atau 10K dan amankan slotmu sekarang.' }}">

        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        <link rel="icon" href="{{ \App\Support\BrandAsset::url('UC Run Logo.PNG') }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ \App\Support\BrandAsset::url('UC Run Logo.PNG') }}">

        {{-- Enable scroll-reveal animations only when JavaScript runs and motion is allowed.
             If the reveal script never starts, show everything after 3 seconds. --}}
        <script>
            if (window.matchMedia('(prefers-reduced-motion: no-preference)').matches && 'IntersectionObserver' in window) {
                document.documentElement.classList.add('js-reveal');
                setTimeout(() => window.revealReady || document.documentElement.classList.remove('js-reveal'), 3000);
            }
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-dvh flex-col">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-brand-yellow focus:px-4 focus:py-2 focus:text-body-sm focus:font-semibold focus:text-primary">
            Lewati ke konten utama
        </a>

        <x-site-header />

        <main id="main-content" class="relative isolate flex-1 overflow-x-clip">
            @if ($decorated)
                <x-page-decor />
            @endif

            <x-flash />

            {{ $slot }}
        </main>

        <x-site-footer />
    </body>
</html>

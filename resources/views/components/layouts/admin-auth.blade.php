@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#713BDB">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $title }} · Admin Console · {{ config('app.name') }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/admin.js'])
    </head>
    <body class="min-h-dvh">
        <main id="main-content" class="flex min-h-dvh items-center justify-center px-4 py-10 sm:py-16">
            {{ $slot }}
        </main>
    </body>
</html>

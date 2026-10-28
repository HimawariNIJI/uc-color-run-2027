<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} | {{ config('uc-color-run.site.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="{{ $bodyClass ?? '' }}">
<main class="page">
    @yield('content')
</main>

<nav class="dev-nav" aria-label="Navigasi halaman">
    <a href="{{ route('category') }}">Race Category</a>
    <a href="{{ route('schedule') }}">Race Schedule</a>
    <a href="{{ route('regulation') }}">Race Regulation</a>
    <a href="{{ route('regulation', ['doc' => 'privacy']) }}">Privacy Policy</a>
</nav>
</body>
</html>

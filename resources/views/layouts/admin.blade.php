@php
    $navItems = [
        ['label' => 'Dashboard',               'route' => 'admin.dashboard',           'match' => 'admin.dashboard'],
        ['label' => 'User Management',         'route' => 'admin.user.index',          'match' => 'admin.user.*'],
        ['label' => 'Registration Management', 'route' => 'admin.registration.index',  'match' => 'admin.registration.*'],
        ['label' => 'BIB Pickup Scanner',      'route' => 'admin.qr-scanner',          'match' => 'admin.qr-scanner'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'UC Color Run 2027')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body>
    <svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true">
        <symbol id="icon-placeholder" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round">
            <path d="M12 2l8.66 5v10L12 22l-8.66-5V7z"/>
            <path d="M12 12l8.66-5M12 12v10M12 12L3.34 7"/>
        </symbol>
        <symbol id="icon-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M5 8h14M5 12h14M5 16h14"/>
        </symbol>
        <symbol id="icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M6 6l12 12M18 6L6 18"/>
        </symbol>
    </svg>

    @include('components.admin.sidebar')

    <div class="main">
        @include('components.admin.header')

        <main class="content">
            @yield('content')
        </main>
    </div>

    <script>
        (function () {
            const toggle = document.getElementById('menuToggle');
            const menu = document.getElementById('mobileMenu');
            const iconOpen = toggle.querySelector('.i-open');
            const iconClose = toggle.querySelector('.i-close');

            function setOpen(open) {
                menu.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open);
                iconOpen.hidden = open;
                iconClose.hidden = !open;
            }

            toggle.addEventListener('click', () => setOpen(!menu.classList.contains('is-open')));

            // Close on outside click, Escape, or when resizing up to desktop
            document.addEventListener('click', (e) => {
                if (!menu.contains(e.target) && !toggle.contains(e.target)) setOpen(false);
            });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
            window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => { if (e.matches) setOpen(false); });
        })();
    </script>

    @stack('scripts')
</body>
</html>
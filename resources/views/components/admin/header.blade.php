<header class="topbar">
    <div class="topbar-inner">
        <div class="topbar-text">
            <h1>{{ $heading }}</h1>
            <p>{{ $subheading }}</p>
        </div>

        {{-- Desktop: admin pill --}}
        <div class="admin-pill">
            <span class="admin-avatar">A</span>
            <span>Administrator</span>
        </div>

        {{-- Tablet / mobile: hamburger --}}
        <button type="button" class="menu-toggle" id="menuToggle" aria-label="Toggle menu" aria-expanded="false"
            aria-controls="mobileMenu">
            <svg class="icon i-open" aria-hidden="true">
                <use href="#icon-menu" />
            </svg>
            <svg class="icon i-close" aria-hidden="true" hidden>
                <use href="#icon-close" />
            </svg>
        </button>
    </div>

    {{-- Tablet / mobile dropdown --}}
    <div class="mobile-menu" id="mobileMenu">
        <div class="menu-card">
            <div class="admin-pill">
                <span class="admin-avatar">A</span>
                <span>Administrator</span>
            </div>
            <hr>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="menu-logout">Log out</button>
            </form>
        </div>
    </div>
</header>

<nav class="bottom-nav" aria-label="Admin navigation">
    @foreach ($navItems as $item)
        @php $active = request()->routeIs($item['match']); @endphp

        <a href="{{ route($item['route'] ?? 'admin.dashboard') }}"
           class="bottom-nav-item {{ $active ? 'is-active' : '' }}"
           @if ($active) aria-current="page" @endif>

            <svg class="icon" aria-hidden="true">
                <use href="#icon-placeholder" />
            </svg>

            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
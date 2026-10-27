<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">LOGO</div>
        <span class="badge-console">ADMIN CONSOLE</span>
    </div>

    <nav class="sidebar-nav" aria-label="Admin menu">
        @foreach ($navItems as $item)
            @php $active = request()->routeIs($item['match']); @endphp
            <a href="{{ route($item['route']) }}" class="nav-link {{ $active ? 'is-active' : '' }}"
                @if ($active) aria-current="page" @endif>
                <svg class="icon" aria-hidden="true">
                    <use href="#icon-placeholder" />
                </svg>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="sidebar-logout">Log out</button>
        </form>
    </div>
</aside>
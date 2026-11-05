{{-- Desktop navigation (1024px and up). Smaller screens use the bottom tab bar. --}}
<aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col overflow-hidden bg-primary text-brand-cream lg:flex">
    <x-brand-image file="Asset 17@3x.png" class="absolute -bottom-2 left-1/2 w-[160%] -translate-x-1/2 opacity-25" />

    <div class="relative px-6 pb-6 pt-7">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex flex-col items-start gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-yellow" aria-label="UC Color Run 2027 Admin Console, ke dashboard">
            <x-logo tone="light" />
            <span class="rounded-full bg-brand-yellow px-3 py-1 text-caption font-semibold uppercase tracking-[0.14em] text-primary">Admin Console</span>
        </a>
    </div>

    <nav class="relative flex-1 overflow-y-auto border-t border-brand-cream/15 px-4 py-6" aria-label="Menu admin">
        <ul class="flex flex-col gap-1.5">
            @foreach (\App\Support\AdminNavigation::main() as $item)
                @php($isActive = request()->routeIs($item['active']))
                <li>
                    <a
                        href="{{ route($item['route']) }}"
                        @if ($isActive) aria-current="page" @endif
                        @class([
                            'flex items-center gap-3 rounded-2xl px-4 py-3 text-nav transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-yellow',
                            'bg-brand-yellow font-semibold text-primary shadow-[0_3px_0_0_var(--color-brand-orange)]' => $isActive,
                            'text-brand-cream/80 hover:bg-white/10 hover:text-brand-cream' => ! $isActive,
                        ])
                    >
                        <x-icon :name="$item['icon']" class="size-5" />
                        <span class="leading-snug">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="relative border-t border-brand-cream/15 px-4 py-5">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-nav text-brand-cream/75 transition-colors hover:bg-white/10 hover:text-brand-yellow focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-yellow">
                <x-icon name="log-out" class="size-5" />
                Log out
            </button>
        </form>
    </div>
</aside>

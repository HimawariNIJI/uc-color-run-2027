{{-- Desktop navigation (1024px and up). Smaller screens use the bottom tab bar. --}}
<aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-border bg-surface lg:flex">
    <div class="px-6 pb-6 pt-7">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex flex-col items-start gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary" aria-label="UC Color Run 2027 Admin Console, ke dashboard">
            <x-logo />
            <span class="rounded-full bg-primary/10 px-3 py-1 text-caption font-semibold uppercase tracking-[0.14em] text-primary">Admin Console</span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto border-t border-border px-4 py-6" aria-label="Menu admin">
        <ul class="flex flex-col gap-1.5">
            @foreach (\App\Support\AdminNavigation::main() as $item)
                @php($isActive = request()->routeIs($item['active']))
                <li>
                    <a
                        href="{{ route($item['route']) }}"
                        @if ($isActive) aria-current="page" @endif
                        @class([
                            'flex items-center gap-3 rounded-2xl px-4 py-3 text-nav transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
                            'bg-primary/10 font-semibold text-primary' => $isActive,
                            'text-foreground/75 hover:bg-foreground/5 hover:text-foreground' => ! $isActive,
                        ])
                    >
                        <x-icon :name="$item['icon']" class="size-5" />
                        <span class="leading-snug">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="border-t border-border px-4 py-5">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-nav text-muted transition-colors hover:bg-error/6 hover:text-error focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                <x-icon name="log-out" class="size-5" />
                Log out
            </button>
        </form>
    </div>
</aside>

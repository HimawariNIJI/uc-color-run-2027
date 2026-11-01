{{-- Tab bar for phones and tablets (below 1024px), replacing the sidebar. --}}
<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-surface pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Menu admin">
    <ul class="mx-auto grid max-w-xl grid-cols-4 gap-1 px-1.5 py-1.5 sm:gap-2 sm:px-3 sm:py-2">
        @foreach (\App\Support\AdminNavigation::main() as $item)
            @php($isActive = request()->routeIs($item['active']))
            <li>
                <a
                    href="{{ route($item['route']) }}"
                    @if ($isActive) aria-current="page" @endif
                    @class([
                        'flex h-full min-h-16 flex-col items-center justify-center gap-1 rounded-2xl px-0.5 py-2 text-center text-[0.6875rem] font-medium leading-tight transition-colors min-[22.5rem]:text-caption focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary',
                        'bg-primary/10 font-semibold text-primary' => $isActive,
                        'text-muted hover:bg-foreground/5 hover:text-foreground' => ! $isActive,
                    ])
                >
                    <x-icon :name="$item['icon']" class="size-5" />
                    <span>{{ $item['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>

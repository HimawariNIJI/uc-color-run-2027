@php
    $navigation = \App\Support\SiteNavigation::main();
    $accountLinks = \App\Support\SiteNavigation::account();
    $user = auth()->user();
@endphp

<header class="sticky top-0 z-50 bg-primary text-brand-cream">
    <div class="page-container flex h-16 items-center justify-between gap-4 lg:h-18">
        <a href="{{ route('home') }}" class="shrink-0 rounded-lg transition-transform duration-200 hover:-rotate-2 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-yellow" aria-label="UC Color Run 2027, ke beranda">
            <x-logo size="responsive" tone="light" />
        </a>

        {{-- Desktop navigation --}}
        <nav class="ml-auto hidden lg:block" aria-label="Navigasi utama">
            <ul class="flex items-center gap-1 xl:gap-2">
                @foreach ($navigation as $item)
                    @php($isActive = request()->routeIs($item['active']))
                    <li>
                        @isset($item['children'])
                            <div class="group relative" data-dropdown>
                                <button
                                    type="button"
                                    @class([
                                        'relative inline-flex items-center gap-1 rounded-lg px-3 py-2 text-nav transition-colors hover:text-brand-yellow focus-visible:outline-2 focus-visible:outline-brand-yellow',
                                        'text-brand-yellow' => $isActive,
                                        'text-brand-cream/90' => ! $isActive,
                                    ])
                                    aria-expanded="false"
                                    aria-controls="race-info-menu"
                                    data-dropdown-trigger
                                >
                                    {{ $item['label'] }}
                                    <x-icon name="chevron-down" class="size-4 transition-transform duration-200 group-data-[open=true]:rotate-180" />
                                    @if ($isActive)
                                        <span class="absolute inset-x-3 -bottom-0.5 h-1 rounded-full bg-secondary" aria-hidden="true"></span>
                                    @endif
                                </button>

                                <div id="race-info-menu" class="absolute left-1/2 top-full z-10 mt-3 w-64 -translate-x-1/2 rounded-2xl border border-border bg-surface p-2 text-foreground shadow-popover" data-dropdown-menu hidden>
                                    <ul class="flex flex-col gap-0.5">
                                        @foreach ($item['children'] as $child)
                                            @php($isChildActive = request()->routeIs($child['route']))
                                            <li>
                                                <a
                                                    href="{{ route($child['route']) }}"
                                                    @if ($isChildActive) aria-current="page" @endif
                                                    class="group/item flex items-center gap-3 rounded-xl px-3 py-2.5 text-body-sm font-medium transition-colors hover:bg-brand-yellow/35 {{ $isChildActive ? 'text-primary' : 'text-foreground' }}"
                                                >
                                                    <span class="grid size-8 place-items-center rounded-lg bg-primary/10 text-primary transition-colors group-hover/item:bg-primary group-hover/item:text-brand-yellow">
                                                        <x-icon :name="$child['icon']" class="size-4" />
                                                    </span>
                                                    {{ $child['label'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @else
                            <a
                                href="{{ route($item['route']) }}"
                                @if ($isActive) aria-current="page" @endif
                                @class([
                                    'relative inline-flex rounded-lg px-3 py-2 text-nav transition-colors hover:text-brand-yellow focus-visible:outline-2 focus-visible:outline-brand-yellow',
                                    'text-brand-yellow' => $isActive,
                                    'text-brand-cream/90' => ! $isActive,
                                ])
                            >
                                {{ $item['label'] }}
                                @if ($isActive)
                                    <span class="absolute inset-x-3 -bottom-0.5 h-1 rounded-full bg-secondary" aria-hidden="true"></span>
                                @endif
                            </a>
                        @endisset
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex items-center gap-2 lg:ml-4 lg:gap-3 xl:ml-6">
            @auth
                <div class="group relative" data-dropdown>
                    <button
                        type="button"
                        class="flex items-center gap-1 rounded-full border border-brand-cream/45 p-1 pr-2.5 text-brand-cream transition-colors hover:border-brand-yellow focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-yellow lg:pr-1"
                        aria-expanded="false"
                        aria-controls="profile-menu"
                        aria-label="Menu akun"
                        data-dropdown-trigger
                    >
                        <x-avatar :user="$user" class="size-8" />
                        <x-icon name="chevron-down" class="size-4 transition-transform duration-200 group-data-[open=true]:rotate-180 lg:hidden" />
                    </button>

                    <div id="profile-menu" class="absolute -right-12 top-full z-10 mt-3 w-[min(17rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-border bg-surface text-foreground shadow-popover lg:right-0" data-dropdown-menu hidden>
                        <div class="flex items-center gap-3 border-b border-border px-4 py-3.5">
                            <x-avatar :user="$user" class="size-11 border border-primary/20" />
                            <div class="min-w-0">
                                <p class="truncate text-body font-semibold">{{ $user->name }}</p>
                                <p class="truncate text-caption text-muted">{{ $user->email }}</p>
                            </div>
                        </div>
                        <ul class="flex flex-col gap-0.5 p-2">
                            @foreach ($accountLinks as $link)
                                <li>
                                    <a href="{{ route($link['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-body-sm font-medium transition-colors hover:bg-brand-yellow/35 {{ request()->routeIs($link['route']) ? 'text-primary' : '' }}">
                                        <x-icon :name="$link['icon']" class="size-4.5" />
                                        {{ $link['label'] }}
                                    </a>
                                </li>
                            @endforeach
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-body-sm font-medium text-error transition-colors hover:bg-error/5">
                                        <x-icon name="log-out" class="size-4.5" />
                                        Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            @else
                <x-button :href="route('login')" size="sm" class="max-sm:min-h-9 max-sm:px-3.5">Sign In</x-button>
            @endauth

            <button
                type="button"
                class="grid size-10 place-items-center rounded-full border border-brand-cream/45 text-brand-cream transition-colors hover:border-brand-yellow hover:text-brand-yellow focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-yellow lg:hidden"
                aria-controls="mobile-menu"
                aria-expanded="false"
                aria-label="Buka menu"
                data-label-open="Buka menu"
                data-label-close="Tutup menu"
                data-mobile-menu-toggle
            >
                <x-icon name="menu" class="size-5" data-icon-open />
                <x-icon name="x" class="size-5" data-icon-close hidden />
            </button>
        </div>
    </div>

    {{-- Colour-run stripe under the header --}}
    <div class="bg-brand-stripe h-1" aria-hidden="true"></div>

    {{-- Mobile & tablet navigation --}}
    <div id="mobile-menu" class="fixed inset-x-0 bottom-0 top-17 overflow-y-auto overscroll-contain bg-surface text-foreground lg:hidden" hidden>
        <nav class="page-container py-4" aria-label="Navigasi utama">
            <ul class="flex flex-col gap-1">
                @foreach ($navigation as $item)
                    @php($isActive = request()->routeIs($item['active']))
                    <li>
                        @isset($item['children'])
                            <button
                                type="button"
                                class="group flex min-h-12 w-full items-center justify-between rounded-xl px-4 text-left text-nav transition-colors hover:bg-brand-yellow/35 {{ $isActive ? 'text-primary' : '' }}"
                                aria-expanded="{{ $isActive ? 'true' : 'false' }}"
                                aria-controls="mobile-race-info"
                                data-collapsible-trigger
                            >
                                {{ $item['label'] }}
                                <x-icon name="chevron-down" class="size-4 transition-transform duration-200 group-aria-expanded:rotate-180" />
                            </button>
                            <ul id="mobile-race-info" class="mb-1 flex flex-col gap-0.5 pl-4" @unless ($isActive) hidden @endunless>
                                @foreach ($item['children'] as $child)
                                    @php($isChildActive = request()->routeIs($child['route']))
                                    <li>
                                        <a
                                            href="{{ route($child['route']) }}"
                                            @if ($isChildActive) aria-current="page" @endif
                                            class="flex min-h-11 items-center gap-3 rounded-xl px-4 text-nav transition-colors {{ $isChildActive ? 'bg-primary text-brand-yellow' : 'text-foreground hover:bg-brand-yellow/35' }}"
                                        >
                                            <x-icon :name="$child['icon']" class="size-4.5 {{ $isChildActive ? '' : 'text-primary' }}" />
                                            {{ $child['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <a
                                href="{{ route($item['route']) }}"
                                @if ($isActive) aria-current="page" @endif
                                class="flex min-h-12 items-center rounded-xl px-4 text-nav transition-colors {{ $isActive ? 'bg-primary text-brand-yellow' : 'text-foreground hover:bg-brand-yellow/35' }}"
                            >
                                {{ $item['label'] }}
                            </a>
                        @endisset
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</header>

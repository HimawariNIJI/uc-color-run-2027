@props(['title', 'subtitle' => null])

@php($admin = auth('admin')->user())

<header class="relative flex items-start justify-between gap-4 pb-6 pt-6 lg:items-center lg:pb-8 lg:pt-10">
    <div class="min-w-0">
        <h1 class="font-display text-h3 font-normal text-primary">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-body-sm text-muted">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="hidden shrink-0 lg:block">
        <x-admin.identity-pill :admin="$admin" />
    </div>

    {{-- Phones and tablets: the account menu sits behind the burger button. --}}
    <div class="shrink-0 lg:hidden" data-dropdown>
        <button
            type="button"
            class="group grid size-12 place-items-center rounded-full border border-border bg-surface text-foreground transition-colors hover:border-foreground/25 aria-expanded:border-primary aria-expanded:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            aria-expanded="false"
            aria-controls="admin-account-menu"
            aria-label="Menu akun"
            data-dropdown-trigger
        >
            <x-icon name="menu" class="size-5.5 group-aria-expanded:hidden" />
            <x-icon name="x" class="hidden size-5.5 group-aria-expanded:block" />
        </button>

        <div id="admin-account-menu" class="absolute inset-x-0 top-full z-40 -mt-2 rounded-3xl border border-border bg-surface p-4 shadow-popover sm:left-auto sm:w-80" hidden data-dropdown-menu>
            <x-admin.identity-pill :admin="$admin" size="lg" class="w-full" />
            <hr class="my-4 border-border">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <x-button type="submit" variant="secondary" pill block>
                    <x-icon name="log-out" class="size-4.5" />
                    Log out
                </x-button>
            </form>
        </div>
    </div>
</header>

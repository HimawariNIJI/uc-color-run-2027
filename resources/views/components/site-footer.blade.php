@php
    $exploreLinks = collect(\App\Support\SiteNavigation::main())->reject(fn (array $item): bool => $item['route'] === 'home');
    $contacts = config('event.contacts');
@endphp

<footer class="border-t border-border bg-surface">
    <div class="page-container grid gap-10 py-12 md:grid-cols-[1fr_auto] md:gap-12 lg:gap-24 lg:py-16">
        <div class="flex flex-col items-start gap-4">
            <a href="{{ route('home') }}" class="rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary" aria-label="UC Color Run 2027, ke beranda">
                <x-logo />
            </a>
            <x-logo variant="organizer" />
        </div>

        <div class="grid gap-8 sm:grid-cols-[repeat(3,auto)] sm:justify-between sm:gap-10 lg:gap-16">
            <div>
                <h2 class="text-body-sm font-bold uppercase tracking-wide">Explore</h2>
                <ul class="mt-4 flex flex-col gap-3">
                    @foreach ($exploreLinks as $link)
                        <li><a href="{{ route($link['route']) }}" class="text-body-sm text-muted transition-colors hover:text-primary">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="text-body-sm font-bold uppercase tracking-wide">Account</h2>
                <ul class="mt-4 flex flex-col gap-3">
                    @auth
                        @foreach (\App\Support\SiteNavigation::account() as $link)
                            <li><a href="{{ route($link['route']) }}" class="text-body-sm text-muted transition-colors hover:text-primary">{{ $link['label'] }}</a></li>
                        @endforeach
                    @else
                        <li><a href="{{ route('login') }}" class="text-body-sm text-muted transition-colors hover:text-primary">Sign In</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <h2 class="text-body-sm font-bold uppercase tracking-wide">Contact Us</h2>
                <ul class="mt-4 flex flex-col gap-3">
                    <li>
                        <a href="mailto:{{ $contacts['email'] }}" class="inline-flex items-center gap-2 text-body-sm wrap-anywhere text-muted transition-colors hover:text-primary">
                            <x-icon name="mail" class="size-4" />{{ $contacts['email'] }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $contacts['whatsapp']['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-body-sm text-muted transition-colors hover:text-primary">
                            <x-icon name="message-circle" class="size-4" />{{ $contacts['whatsapp']['display'] }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $contacts['instagram']['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-body-sm text-muted transition-colors hover:text-primary">
                            <x-icon name="instagram" class="size-4" />{{ $contacts['instagram']['display'] }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-border">
        <p class="page-container py-5 text-center text-caption text-muted">
            &copy; {{ now()->year }} UC Color Run 2027 &middot; Universitas Ciputra. Hak cipta dilindungi.
        </p>
    </div>
</footer>

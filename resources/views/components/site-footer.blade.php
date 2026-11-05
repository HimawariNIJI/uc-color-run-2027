@php
    $exploreLinks = collect(\App\Support\SiteNavigation::main())->reject(fn (array $item): bool => $item['route'] === 'home');
    $contacts = config('event.contacts');
    $linkClasses = 'rounded text-body-sm text-brand-cream/80 transition-colors hover:text-brand-yellow focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-yellow';
@endphp

<footer class="relative isolate mt-10 text-brand-cream sm:mt-14">
    {{-- Footer artwork from the Design Division: its jagged colour edge sits on top of the indigo band.
         The image has a large transparent top margin, so it is anchored to its bottom edge. --}}
    <div
        class="pointer-events-none absolute inset-x-0 top-0 -z-10 aspect-[4099/1067] bg-[length:100%_auto] bg-bottom bg-no-repeat"
        style="background-image: url('{{ \App\Support\BrandAsset::url('Footer.png') }}')"
        aria-hidden="true"
    ></div>
    <div class="absolute inset-x-0 bottom-0 top-[8vw] -z-20 bg-primary" aria-hidden="true"></div>

    <div class="page-container grid gap-10 pb-12 pt-[calc(9vw+2.5rem)] md:grid-cols-[1fr_auto] md:gap-12 lg:gap-24 lg:pb-16">
        <div class="flex flex-col items-start gap-4">
            <a href="{{ route('home') }}" class="rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-yellow" aria-label="UC Color Run 2027, ke beranda">
                <x-logo size="xl" tone="light" />
            </a>
            <x-logo variant="organizer" tone="light" />
        </div>

        <div class="grid gap-8 sm:grid-cols-[repeat(3,auto)] sm:justify-between sm:gap-10 lg:gap-16">
            <div>
                <h2 class="font-display text-body-lg uppercase tracking-wide text-brand-yellow">Explore</h2>
                <ul class="mt-4 flex flex-col gap-3">
                    @foreach ($exploreLinks as $link)
                        <li><a href="{{ route($link['route']) }}" class="{{ $linkClasses }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="font-display text-body-lg uppercase tracking-wide text-brand-yellow">Account</h2>
                <ul class="mt-4 flex flex-col gap-3">
                    @auth
                        @foreach (\App\Support\SiteNavigation::account() as $link)
                            <li><a href="{{ route($link['route']) }}" class="{{ $linkClasses }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    @else
                        <li><a href="{{ route('login') }}" class="{{ $linkClasses }}">Sign In</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <h2 class="font-display text-body-lg uppercase tracking-wide text-brand-yellow">Contact Us</h2>
                <ul class="mt-4 flex flex-col gap-3">
                    <li>
                        <a href="mailto:{{ $contacts['email'] }}" class="inline-flex items-center gap-2 wrap-anywhere {{ $linkClasses }}">
                            <x-icon name="mail" class="size-4" />{{ $contacts['email'] }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $contacts['whatsapp']['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 {{ $linkClasses }}">
                            <x-icon name="message-circle" class="size-4" />{{ $contacts['whatsapp']['display'] }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $contacts['instagram']['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 {{ $linkClasses }}">
                            <x-icon name="instagram" class="size-4" />{{ $contacts['instagram']['display'] }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-brand-cream/15">
        <p class="page-container py-5 text-center text-caption text-brand-cream/70">
            &copy; {{ now()->year }} UC Color Run 2027 &middot; Universitas Ciputra. Hak cipta dilindungi.
        </p>
    </div>
</footer>

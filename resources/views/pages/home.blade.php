<x-layouts.app :decorated="false">
    {{-- Banner --}}
    <section class="relative isolate overflow-hidden bg-primary text-brand-cream" aria-label="Banner UC Color Run 2027">
        @if ($bannerImage)
            <img src="{{ asset($bannerImage) }}" alt="UC Color Run 2027" class="h-72 w-full object-cover sm:h-96 lg:h-[28rem]">
        @else
            <div class="bg-powder relative flex min-h-[25rem] flex-col items-center justify-center px-4 pb-32 pt-12 text-center sm:min-h-[31rem] sm:pb-40 lg:min-h-[35rem] lg:pb-48">
                {{-- Sky --}}
                <x-brand-image file="5@3x.png" eager class="absolute -right-10 top-4 -z-10 w-36 motion-safe:animate-float-slow sm:right-[4%] sm:w-52 lg:w-64" />
                <x-brand-image file="2@3x.png" eager class="absolute -left-20 top-8 -z-10 w-52 opacity-80 motion-safe:animate-float sm:-left-10 sm:w-72 lg:w-96" />
                <x-brand-image file="3@3x.png" eager class="absolute right-[18%] top-[46%] -z-10 hidden w-56 opacity-60 motion-safe:animate-drift lg:block" />
                <div class="bg-dots absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>

                {{-- Landscape --}}
                <x-brand-image file="Asset 16@3x.png" eager class="absolute -bottom-2 -left-[12%] -z-10 w-[85%] opacity-90 sm:w-[55%] lg:w-[40%]" />
                <x-brand-image file="Asset 17@3x.png" eager class="absolute -bottom-3 left-1/2 -z-10 w-[170%] -translate-x-1/2 sm:w-[120%] lg:-bottom-10 lg:w-[96%]" />
                <x-brand-image file="Asset 10@3x.png" eager class="absolute bottom-0 left-[3%] -z-10 w-12 sm:w-16 lg:w-20" />
                <x-brand-image file="Asset 13@3x.png" eager class="absolute bottom-0 left-[10%] -z-10 w-8 sm:w-11 lg:w-14" />
                <x-brand-image file="Asset 11@3x.png" eager class="absolute bottom-0 right-[12%] -z-10 w-9 sm:w-12 lg:w-16" />
                <x-brand-image file="Asset 12@3x.png" eager class="absolute bottom-0 right-[3%] -z-10 w-14 sm:w-[4.5rem] lg:w-24" />

                <p class="text-caption font-semibold uppercase tracking-[0.3em] text-brand-yellow motion-safe:animate-rise sm:text-body-sm">Universitas Ciputra presents</p>
                <p class="mt-3 font-display text-[clamp(2.75rem,8.5vw,6rem)] uppercase leading-[0.92] [text-shadow:0_4px_0_rgb(34_17_79/0.35)]">
                    <span class="block motion-safe:animate-rise [animation-delay:120ms]">UC Color Run</span>
                    <span class="block text-brand-yellow motion-safe:animate-rise [animation-delay:240ms]">2027</span>
                </p>
                <ul class="mt-6 flex items-center gap-2 text-body-sm font-semibold motion-safe:animate-rise [animation-delay:360ms] sm:gap-3" aria-label="Kategori lomba">
                    @foreach (\App\Enums\RaceCategory::cases() as $category)
                        <li class="rounded-full border border-brand-cream/45 bg-primary/40 px-3.5 py-1 backdrop-blur-sm">{{ $category->label() }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    {{-- Intro --}}
    <section class="page-container page-section relative">
        <x-brand-image file="dark 1@3x.png" class="absolute -right-10 top-10 -z-10 hidden w-72 opacity-20 lg:block" />

        <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-16">
            <div class="max-w-3xl" data-reveal>
                <p class="inline-flex items-center gap-2 rounded-full bg-secondary/12 px-3.5 py-1.5 text-body-sm font-semibold text-secondary-strong">
                    <x-icon name="calendar" class="size-4" />
                    {{ $raceDay->translatedFormat('l, j F Y') }}
                </p>
                <h1 class="mt-5 text-h1 text-primary">Siap Berlari dan Bermandikan Warna?</h1>
                <p class="mt-4 text-body-lg text-muted">
                    UC Color Run 2027 kembali hadir di Universitas Ciputra Surabaya! Rasakan serunya berlari melewati color zone penuh warna bersama ribuan pelari, komunitas, dan keluarga. Pilih kategori 3K, 5K, atau 10K sesuai kemampuanmu dan jadikan garis finish sebagai perayaan.
                </p>
                <p class="mt-6 text-body font-semibold">
                    @if ($isEarlyBirdAvailable)
                        Kuota terbatas! Slot Early Bird masih tersedia, amankan harga spesialmu sekarang.
                    @else
                        Kuota terbatas! Daftar sekarang dan amankan slotmu di garis start.
                    @endif
                </p>
                <x-button :href="route('registration.type')" class="mt-5">
                    Register Now
                    <x-icon name="arrow-right" class="size-4.5" />
                </x-button>
            </div>

            <dl class="grid gap-4 rounded-3xl border-2 border-primary/10 bg-surface p-6 shadow-card sm:grid-cols-3 lg:grid-cols-1 lg:p-7" data-reveal style="--reveal-delay: 120ms">
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-yellow text-primary"><x-icon name="calendar" class="size-5" /></span>
                    <div>
                        <dt class="text-caption font-medium uppercase tracking-wider text-muted">Race Day</dt>
                        <dd class="mt-0.5 text-body-sm font-semibold">{{ $raceDay->translatedFormat('j F Y') }}, {{ $raceDay->format('H.i') }} WIB</dd>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-accent-pink/15 text-accent-pink-strong"><x-icon name="map-pin" class="size-5" /></span>
                    <div>
                        <dt class="text-caption font-medium uppercase tracking-wider text-muted">Lokasi</dt>
                        <dd class="mt-0.5 text-body-sm font-semibold">{{ $venue['name'] }}</dd>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-accent-violet/15 text-accent-violet-strong"><x-icon name="flag" class="size-5" /></span>
                    <div>
                        <dt class="text-caption font-medium uppercase tracking-wider text-muted">Kategori</dt>
                        <dd class="mt-0.5 text-body-sm font-semibold">3 KM &middot; 5 KM &middot; 10 KM</dd>
                    </div>
                </div>
            </dl>
        </div>
    </section>

    {{-- Timeline --}}
    <section class="relative isolate overflow-hidden bg-primary text-brand-cream">
        <div class="bg-powder absolute inset-0 -z-10 opacity-70" aria-hidden="true"></div>
        <x-brand-image file="Asset 15@3x.png" class="absolute -right-[20%] top-1/2 -z-10 w-[120%] -translate-y-1/2 opacity-25 md:-right-[8%] md:w-[85%]" />

        <div class="page-container page-section">
            <x-section-heading title="Our Timeline" tone="light" description="Catat tanggal-tanggal pentingnya dan jangan sampai ketinggalan." />

            <ol @class([
                'mt-10 grid md:mt-14 md:gap-6',
                'md:grid-cols-3' => $timeline->count() === 3,
                'md:grid-cols-4' => $timeline->count() >= 4,
            ])>
                @foreach ($timeline as $milestone)
                    <li class="relative flex gap-4 pb-8 last:pb-0 md:flex-col md:gap-0 md:pb-0" data-reveal style="--reveal-delay: {{ $loop->index * 120 }}ms">
                        @unless ($loop->last)
                            <span @class([
                                'absolute left-[11px] top-7 bottom-1 w-1 rounded-full md:left-7 md:-right-5 md:top-[10px] md:bottom-auto md:h-1 md:w-auto',
                                'bg-brand-yellow' => $milestone['is_past'],
                                'bg-brand-cream/25' => ! $milestone['is_past'],
                            ]) aria-hidden="true"></span>
                        @endunless

                        <span @class([
                            'relative grid size-6 shrink-0 place-items-center rounded-full border-2',
                            'border-brand-yellow bg-brand-yellow text-primary' => $milestone['is_past'],
                            'border-secondary bg-secondary ring-4 ring-secondary/30' => $milestone['is_current'],
                            'border-brand-cream/45 bg-primary' => ! $milestone['is_past'] && ! $milestone['is_current'],
                        ]) aria-hidden="true">
                            @if ($milestone['is_past'])
                                <x-icon name="check" class="size-3.5" stroke-width="3" />
                            @elseif ($milestone['is_current'])
                                <span class="size-2 rounded-full bg-brand-cream motion-safe:animate-pulse"></span>
                            @endif
                        </span>

                        <div class="min-w-0 md:mt-5 md:pr-4">
                            <p class="text-body-sm font-semibold text-brand-yellow">{{ $milestone['dates'] }}</p>
                            <h3 class="mt-1 font-display text-h4 text-brand-cream">{{ $milestone['title'] }}</h3>
                            @if ($milestone['is_current'])
                                <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-brand-yellow px-2.5 py-1 text-caption font-semibold text-primary">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    Sedang berlangsung
                                </span>
                            @endif
                            <p class="mt-2 text-body-sm text-brand-cream/80">{{ $milestone['description'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Recap video --}}
    <section class="page-container page-section relative">
        <x-brand-image file="yellow@3x.png" class="absolute -left-52 top-20 -z-10 hidden w-80 opacity-40 motion-safe:animate-float-slow xl:block" />

        <x-section-heading title="UC Color Run 2026 Recap" description="Intip keseruan UC Color Run tahun lalu, lalu bayangkan dirimu di garis finish tahun ini." />

        <div class="mx-auto mt-10 max-w-4xl" data-reveal="clip">
            @if ($recapVideoUrl)
                <div class="aspect-video overflow-hidden rounded-3xl border-[3px] border-primary bg-foreground shadow-card">
                    <iframe
                        src="{{ $recapVideoUrl }}"
                        title="Video recap UC Color Run 2026"
                        class="size-full"
                        loading="lazy"
                        allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
            @else
                <x-media class="aspect-video rounded-3xl border-[3px] border-primary shadow-card" icon="play" tone="pink" label="Video recap segera hadir" alt="Video recap UC Color Run 2026" />
            @endif
        </div>
    </section>

    {{-- Sponsors --}}
    <section>
        <div class="page-container page-section pt-0 md:pt-0">
            <x-section-heading title="Our Sponsors & Partners" description="Terima kasih kepada para mitra yang mendukung terselenggaranya UC Color Run 2027." />

            <div class="mt-10 flex items-center gap-2 sm:gap-4" data-carousel data-reveal>
                <button type="button" class="grid size-9 shrink-0 place-items-center rounded-full border-2 border-primary/15 bg-surface text-primary transition hover:border-primary hover:bg-brand-yellow disabled:pointer-events-none disabled:opacity-35 sm:size-11" aria-label="Sponsor sebelumnya" data-carousel-prev>
                    <x-icon name="chevron-left" class="size-5" />
                </button>

                <ul class="flex min-w-0 flex-1 snap-x snap-mandatory gap-3 overflow-x-auto scrollbar-none sm:gap-4" aria-label="Daftar sponsor" data-carousel-track>
                    @foreach ($sponsors as $sponsor)
                        <li class="w-[calc((100%-0.75rem)/2)] shrink-0 snap-start sm:w-[calc((100%-2rem)/3)] lg:w-[calc((100%-3rem)/4)]">
                            <div class="grid aspect-[3/2] place-items-center rounded-2xl border-2 border-primary/10 bg-background p-4 transition duration-200 hover:border-primary/30 hover:bg-brand-yellow/25">
                                @if ($sponsor['logo'])
                                    <img src="{{ asset($sponsor['logo']) }}" alt="{{ $sponsor['name'] }}" class="max-h-full max-w-full object-contain" loading="lazy">
                                @else
                                    <span class="font-display text-body-lg text-primary/60">{{ $sponsor['name'] }}</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                <button type="button" class="grid size-9 shrink-0 place-items-center rounded-full border-2 border-primary/15 bg-surface text-primary transition hover:border-primary hover:bg-brand-yellow disabled:pointer-events-none disabled:opacity-35 sm:size-11" aria-label="Sponsor berikutnya" data-carousel-next>
                    <x-icon name="chevron-right" class="size-5" />
                </button>
            </div>
        </div>
    </section>
</x-layouts.app>

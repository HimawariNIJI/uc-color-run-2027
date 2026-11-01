<x-layouts.app>
    {{-- Banner --}}
    <section class="relative isolate overflow-hidden bg-primary text-white" aria-label="Banner UC Color Run 2027">
        @if ($bannerImage)
            <img src="{{ asset($bannerImage) }}" alt="UC Color Run 2027" class="h-72 w-full object-cover sm:h-96 lg:h-[28rem]">
        @else
            <div class="relative flex h-72 flex-col items-center justify-center px-4 text-center sm:h-96 lg:h-[28rem]">
                <div class="absolute -left-20 -top-28 -z-10 size-72 rounded-full bg-secondary/70 blur-3xl sm:size-[26rem]" aria-hidden="true"></div>
                <div class="absolute -bottom-40 left-1/4 -z-10 size-80 rounded-full bg-accent-cyan/55 blur-3xl sm:size-[30rem]" aria-hidden="true"></div>
                <div class="absolute -right-28 -top-16 -z-10 size-72 rounded-full bg-secondary/45 blur-3xl sm:size-[24rem]" aria-hidden="true"></div>
                <div class="bg-dots absolute inset-0 -z-10" aria-hidden="true"></div>

                <p class="text-caption font-semibold uppercase tracking-[0.3em] text-white/85 sm:text-body-sm">Universitas Ciputra presents</p>
                <p class="mt-3 text-[clamp(2.75rem,8.5vw,6rem)] font-extrabold uppercase leading-[0.92] tracking-tight">
                    UC Color Run
                    <span class="block text-accent-yellow">2027</span>
                </p>
                <ul class="mt-6 flex items-center gap-2 text-body-sm font-semibold sm:gap-3" aria-label="Kategori lomba">
                    @foreach (\App\Enums\RaceCategory::cases() as $category)
                        <li class="rounded-full border border-white/40 bg-white/10 px-3.5 py-1 backdrop-blur-sm">{{ $category->label() }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    {{-- Intro --}}
    <section class="page-container page-section">
        <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-16">
            <div class="max-w-3xl">
                <p class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3.5 py-1.5 text-body-sm font-semibold text-primary">
                    <x-icon name="calendar" class="size-4" />
                    {{ $raceDay->translatedFormat('l, j F Y') }}
                </p>
                <h1 class="mt-5 text-h1">Siap Berlari dan Bermandikan Warna?</h1>
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
                <x-button :href="route('registration.type')" class="mt-4">
                    Register Now
                    <x-icon name="arrow-right" class="size-4.5" />
                </x-button>
            </div>

            <dl class="grid gap-4 rounded-3xl border border-border bg-surface p-6 shadow-card sm:grid-cols-3 lg:grid-cols-1 lg:p-7">
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"><x-icon name="calendar" class="size-5" /></span>
                    <div>
                        <dt class="text-caption font-medium uppercase tracking-wider text-muted">Race Day</dt>
                        <dd class="mt-0.5 text-body-sm font-semibold">{{ $raceDay->translatedFormat('j F Y') }}, {{ $raceDay->format('H.i') }} WIB</dd>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"><x-icon name="map-pin" class="size-5" /></span>
                    <div>
                        <dt class="text-caption font-medium uppercase tracking-wider text-muted">Lokasi</dt>
                        <dd class="mt-0.5 text-body-sm font-semibold">{{ $venue['name'] }}</dd>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"><x-icon name="flag" class="size-5" /></span>
                    <div>
                        <dt class="text-caption font-medium uppercase tracking-wider text-muted">Kategori</dt>
                        <dd class="mt-0.5 text-body-sm font-semibold">3 KM &middot; 5 KM &middot; 10 KM</dd>
                    </div>
                </div>
            </dl>
        </div>
    </section>

    {{-- Timeline --}}
    <section class="border-y border-border bg-surface">
        <div class="page-container page-section">
            <x-section-heading title="Our Timeline" description="Catat tanggal-tanggal pentingnya dan jangan sampai ketinggalan." />

            <ol @class([
                'mt-10 grid md:mt-14 md:gap-6',
                'md:grid-cols-3' => $timeline->count() === 3,
                'md:grid-cols-4' => $timeline->count() >= 4,
            ])>
                @foreach ($timeline as $milestone)
                    <li class="relative flex gap-4 pb-8 last:pb-0 md:flex-col md:gap-0 md:pb-0">
                        @unless ($loop->last)
                            <span @class([
                                'absolute left-[11px] top-7 bottom-1 w-0.5 md:left-7 md:-right-5 md:top-[11px] md:bottom-auto md:h-0.5 md:w-auto',
                                'bg-primary' => $milestone['is_past'],
                                'bg-border' => ! $milestone['is_past'],
                            ]) aria-hidden="true"></span>
                        @endunless

                        <span @class([
                            'relative grid size-6 shrink-0 place-items-center rounded-full border-2',
                            'border-primary bg-primary text-white' => $milestone['is_past'],
                            'border-primary bg-primary ring-4 ring-primary/15' => $milestone['is_current'],
                            'border-border bg-surface' => ! $milestone['is_past'] && ! $milestone['is_current'],
                        ]) aria-hidden="true">
                            @if ($milestone['is_past'])
                                <x-icon name="check" class="size-3.5" stroke-width="3" />
                            @elseif ($milestone['is_current'])
                                <span class="size-2 rounded-full bg-white"></span>
                            @endif
                        </span>

                        <div class="min-w-0 md:mt-5">
                            <p class="text-body-sm font-semibold text-primary">{{ $milestone['dates'] }}</p>
                            <h3 class="mt-1 text-h4">{{ $milestone['title'] }}</h3>
                            @if ($milestone['is_current'])
                                <x-badge tone="success" class="mt-2">Sedang berlangsung</x-badge>
                            @endif
                            <p class="mt-2 text-body-sm text-muted">{{ $milestone['description'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Recap video --}}
    <section class="page-container page-section">
        <x-section-heading title="UC Color Run 2026 Recap" description="Intip keseruan UC Color Run tahun lalu, lalu bayangkan dirimu di garis finish tahun ini." />

        <div class="mx-auto mt-10 max-w-4xl">
            @if ($recapVideoUrl)
                <div class="aspect-video overflow-hidden rounded-3xl bg-foreground shadow-card">
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
                <x-media class="aspect-video rounded-3xl border border-border shadow-card" icon="play" tone="pink" label="Video recap segera hadir" alt="Video recap UC Color Run 2026" />
            @endif
        </div>
    </section>

    {{-- Sponsors --}}
    <section class="border-t border-border bg-surface">
        <div class="page-container page-section">
            <x-section-heading title="Our Sponsors & Partners" description="Terima kasih kepada para mitra yang mendukung terselenggaranya UC Color Run 2027." />

            <div class="mt-10 flex items-center gap-2 sm:gap-4" data-carousel>
                <button type="button" class="grid size-9 shrink-0 place-items-center rounded-full border border-border bg-surface text-foreground transition hover:border-primary hover:text-primary disabled:pointer-events-none disabled:opacity-35 sm:size-11" aria-label="Sponsor sebelumnya" data-carousel-prev>
                    <x-icon name="chevron-left" class="size-5" />
                </button>

                <ul class="flex min-w-0 flex-1 snap-x snap-mandatory gap-3 overflow-x-auto scrollbar-none sm:gap-4" aria-label="Daftar sponsor" data-carousel-track>
                    @foreach ($sponsors as $sponsor)
                        <li class="w-[calc((100%-0.75rem)/2)] shrink-0 snap-start sm:w-[calc((100%-2rem)/3)] lg:w-[calc((100%-3rem)/4)]">
                            <div class="grid aspect-[3/2] place-items-center rounded-2xl border border-border bg-background p-4">
                                @if ($sponsor['logo'])
                                    <img src="{{ asset($sponsor['logo']) }}" alt="{{ $sponsor['name'] }}" class="max-h-full max-w-full object-contain" loading="lazy">
                                @else
                                    <span class="text-body-sm font-semibold text-muted">{{ $sponsor['name'] }}</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                <button type="button" class="grid size-9 shrink-0 place-items-center rounded-full border border-border bg-surface text-foreground transition hover:border-primary hover:text-primary disabled:pointer-events-none disabled:opacity-35 sm:size-11" aria-label="Sponsor berikutnya" data-carousel-next>
                    <x-icon name="chevron-right" class="size-5" />
                </button>
            </div>
        </div>
    </section>
</x-layouts.app>

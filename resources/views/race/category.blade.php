<x-layouts.app title="Race Category" description="Kategori lomba UC Color Run 2027: 3K, 5K, dan 10K, lengkap dengan biaya registrasi dan info lomba.">
    <div class="page-container page-section">
        <div class="mx-auto max-w-content">
            <x-panel-heading
                title="Race Category"
                :divider="false"
                description="Pilih jarak yang paling sesuai dengan kemampuan dan targetmu. Semua kategori melewati color zone yang sama serunya, dengan rute yang dirancang aman di area kampus Universitas Ciputra."
            />

            <div data-tabs data-tabs-param="category">
                <div role="tablist" aria-label="Kategori lomba" class="mt-8 grid grid-cols-3 gap-2.5 sm:gap-4 md:mt-10 md:gap-5">
                    @foreach ($categories as $category)
                        @php($isSelected = $category === $selectedCategory)
                        <button
                            type="button"
                            role="tab"
                            id="category-tab-{{ $category->value }}"
                            aria-controls="category-panel-{{ $category->value }}"
                            aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                            tabindex="{{ $isSelected ? 0 : -1 }}"
                            data-tab-value="{{ $category->value }}"
                            class="group overflow-hidden rounded-2xl border border-border bg-surface text-left shadow-card transition duration-200 hover:-translate-y-0.5 hover:border-primary/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary aria-selected:border-primary aria-selected:ring-4 aria-selected:ring-primary/15 motion-reduce:transform-none"
                        >
                            <span class="block border-b border-border px-1.5 py-2 text-center text-caption font-semibold uppercase leading-tight tracking-wide text-muted transition-colors group-aria-selected:border-primary group-aria-selected:bg-primary group-aria-selected:text-white sm:px-3 sm:py-2.5 sm:tracking-wider">
                                UC Color Run
                            </span>
                            <x-media :icon="false" class="aspect-square rounded-none transition-colors sm:aspect-[4/3]" tone="primary">
                                <span class="text-h2 font-extrabold tracking-tight text-foreground/80 transition-colors group-aria-selected:text-primary sm:text-h1">{{ $category->label() }}</span>
                            </x-media>
                        </button>
                    @endforeach
                </div>

                @foreach ($categories as $category)
                    @php($details = $category->details())
                    <div
                        role="tabpanel"
                        id="category-panel-{{ $category->value }}"
                        aria-labelledby="category-tab-{{ $category->value }}"
                        tabindex="0"
                        class="mt-4 rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary sm:mt-6"
                        @unless ($category === $selectedCategory) hidden @endunless
                    >
                        <p class="mb-4 text-center text-body text-muted sm:mb-6">{{ $details['tagline'] }}</p>

                        <div class="grid gap-4 sm:gap-6 md:grid-cols-2">
                            <section class="overflow-hidden rounded-2xl border border-border bg-surface shadow-card">
                                <h2 class="border-b border-border px-5 py-3 text-center text-body-sm font-semibold uppercase tracking-wider">Registration Fee</h2>
                                <div class="flex flex-col gap-5 p-5 sm:p-6">
                                    @foreach (\App\Enums\FeePeriod::cases() as $period)
                                        <div>
                                            <div class="flex items-center justify-between gap-2">
                                                <h3 class="text-body font-semibold">{{ $period->label() }}</h3>
                                                @if ($period === \App\Enums\FeePeriod::EarlyBird)
                                                    @if ($earlyBirdSlotsLeft[$category->value] > 0)
                                                        <x-badge tone="success">Sisa {{ \App\Support\NumberFormat::integer($earlyBirdSlotsLeft[$category->value]) }} slot</x-badge>
                                                    @else
                                                        <x-badge>Sold out</x-badge>
                                                    @endif
                                                @endif
                                            </div>
                                            <dl class="mt-2.5 flex flex-col gap-2">
                                                @foreach ($registrationTypes as $type)
                                                    <x-detail-row :label="$type->label()">{{ \App\Support\Rupiah::format($category->feeFor($type, $period)) }}</x-detail-row>
                                                @endforeach
                                            </dl>
                                        </div>
                                    @endforeach
                                    <p class="border-t border-border pt-4 text-caption text-muted">Early Bird terbatas {{ \App\Support\NumberFormat::integer(\App\Support\EarlyBirdQuota::slots($category)) }} slot untuk {{ $category->distance() }}. Setelah slot habis, berlaku Normal Price.</p>
                                </div>
                            </section>

                            <section class="overflow-hidden rounded-2xl border border-border bg-surface shadow-card">
                                <h2 class="border-b border-border px-5 py-3 text-center text-body-sm font-semibold uppercase tracking-wider">Race Info</h2>
                                <dl class="flex flex-col gap-4 p-5 sm:p-6">
                                    <div class="flex items-start gap-3">
                                        <x-icon name="calendar" class="mt-0.5 size-5 text-primary" />
                                        <div>
                                            <dt class="text-body-sm text-muted">Race Day</dt>
                                            <dd class="text-body font-semibold">{{ $raceDay->translatedFormat('l, j F Y') }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <x-icon name="clock" class="mt-0.5 size-5 text-primary" />
                                        <div>
                                            <dt class="text-body-sm text-muted">Waktu Start</dt>
                                            <dd class="text-body font-semibold">{{ $details['start_time'] }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <x-icon name="flag" class="mt-0.5 size-5 text-primary" />
                                        <div>
                                            <dt class="text-body-sm text-muted">Cut Off Time</dt>
                                            <dd class="text-body font-semibold">{{ $details['cut_off'] }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <x-icon name="map-pin" class="mt-0.5 size-5 text-primary" />
                                        <div>
                                            <dt class="text-body-sm text-muted">Lokasi</dt>
                                            <dd class="text-body font-semibold">{{ config('event.venue.name') }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </section>
                        </div>

                        <section class="mt-4 sm:mt-6">
                            <h2 class="sr-only">Rute Lari {{ $category->label() }}</h2>
                            <x-media
                                :src="$details['route_image'] ? asset($details['route_image']) : null"
                                :alt="'Peta rute lari '.$category->label()"
                                class="aspect-[4/3] border border-border sm:aspect-[16/9]"
                                icon="map"
                                tone="primary"
                                :label="'Peta rute '.$category->distance().' akan segera diumumkan'"
                            />
                        </section>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex justify-center md:mt-10">
                <x-button :href="route('registration.type')" size="lg" pill class="min-w-56">Register Now</x-button>
            </div>
        </div>
    </div>
</x-layouts.app>

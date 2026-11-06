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
                            class="group overflow-hidden rounded-2xl border-2 border-primary/10 bg-surface text-left shadow-card transition duration-200 ease-spring hover:-translate-y-1 hover:border-primary/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary aria-selected:border-primary aria-selected:shadow-[0_6px_0_0_var(--color-brand-orange)] motion-reduce:transform-none"
                        >
                            <span class="block border-b border-primary/10 px-1.5 py-2 text-center text-caption font-semibold uppercase leading-tight tracking-wide text-muted transition-colors group-aria-selected:border-primary group-aria-selected:bg-primary group-aria-selected:text-brand-yellow sm:px-3 sm:py-2.5 sm:tracking-wider">
                                UC Color Run
                            </span>
                            {{-- The photo is decorative (empty alt): the tab is already named "UC Color Run 3K" by its text. --}}
                            <x-media :icon="false" class="aspect-square rounded-none sm:aspect-[4/3]" :src="\App\Support\BrandAsset::url($category->details()['photo'])">
                                <span class="absolute inset-x-0 bottom-0 h-2/3 bg-linear-to-t from-primary/85 via-primary/30 to-transparent" aria-hidden="true"></span>
                                <span class="absolute inset-x-0 bottom-0 pb-2 font-display text-h2 text-brand-cream [text-shadow:0_2px_8px_rgb(34_17_79/0.6)] transition duration-300 group-hover:scale-105 group-aria-selected:text-brand-yellow sm:pb-3 sm:text-h1">{{ $category->label() }}</span>
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
                            <section class="rounded-3xl border border-border bg-surface p-4 shadow-card sm:p-5" data-reveal>
                                <h2 class="pb-4 text-center font-display text-h4 uppercase tracking-wider text-primary">Registration Fee</h2>
                                <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-1 lg:grid-cols-2">
                                    @foreach (\App\Enums\FeePeriod::cases() as $period)
                                        @php($slotsLeft = $earlyBirdSlotsLeft[$category->value])
                                        <x-fee-container
                                            :title="$period->label()"
                                            :prices="collect($registrationTypes)->mapWithKeys(fn ($type): array => [$type->label() => $category->feeFor($type, $period)])->all()"
                                            :status="$period === \App\Enums\FeePeriod::EarlyBird ? ($slotsLeft > 0 ? 'Sisa '.\App\Support\NumberFormat::integer($slotsLeft).' slot' : 'Sold out') : null"
                                            :unavailable="$period === \App\Enums\FeePeriod::EarlyBird && $slotsLeft === 0"
                                        />
                                    @endforeach
                                </div>
                                <p class="mt-4 text-center text-caption text-muted">Early Bird terbatas {{ \App\Support\NumberFormat::integer(\App\Support\EarlyBirdQuota::slots($category)) }} slot untuk {{ $category->distance() }}. Setelah slot habis, berlaku Normal Price.</p>
                            </section>

                            <section class="overflow-hidden rounded-3xl border-[3px] border-primary bg-surface shadow-card" data-reveal style="--reveal-delay: 120ms">
                                <h2 class="bg-primary px-5 py-4 text-center font-display text-h4 uppercase tracking-wider text-brand-yellow">Race Info</h2>
                                <dl class="flex flex-col gap-4 p-5 sm:p-6">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-yellow text-primary"><x-icon name="calendar" class="size-5" /></span>
                                        <div>
                                            <dt class="text-body-sm text-muted">Race Day</dt>
                                            <dd class="text-body font-semibold">{{ $raceDay->translatedFormat('l, j F Y') }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-yellow text-primary"><x-icon name="clock" class="size-5" /></span>
                                        <div>
                                            <dt class="text-body-sm text-muted">Waktu Start</dt>
                                            <dd class="text-body font-semibold">{{ $details['start_time'] }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-yellow text-primary"><x-icon name="flag" class="size-5" /></span>
                                        <div>
                                            <dt class="text-body-sm text-muted">Cut Off Time</dt>
                                            <dd class="text-body font-semibold">{{ $details['cut_off'] }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-yellow text-primary"><x-icon name="map-pin" class="size-5" /></span>
                                        <div>
                                            <dt class="text-body-sm text-muted">Lokasi</dt>
                                            <dd class="text-body font-semibold">{{ config('event.venue.name') }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </section>
                        </div>

                        <section class="mt-6 sm:mt-8" data-reveal>
                            <h2 class="sr-only">Rute Lari {{ $category->label() }}</h2>
                            <x-route-map
                                :src="$details['route_image'] ? asset($details['route_image']) : null"
                                :alt="'Peta rute lari '.$category->label()"
                                :label="'Peta rute '.$category->distance().' akan segera diumumkan'"
                            />
                        </section>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex justify-center md:mt-10">
                <x-button :href="route('registration.type')" size="lg" class="min-w-56">Register Now <x-icon name="arrow-right" class="size-4.5" /></x-button>
            </div>
        </div>
    </div>
</x-layouts.app>

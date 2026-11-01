<x-layouts.app title="Race Schedule" description="Hitung mundur menuju race day UC Color Run 2027.">
    <div class="page-container page-section">
        <x-panel>
            <x-panel-heading
                title="Race Schedule"
                :divider="false"
                description="Hitung mundur menuju hari perlombaan UC Color Run 2027. Siapkan dirimu, atur latihan, dan jangan sampai ketinggalan!"
            />

            <div
                class="relative isolate mx-auto mt-8 max-w-2xl overflow-hidden rounded-3xl bg-primary px-2 py-8 text-white shadow-lg shadow-primary/25 sm:px-8 md:mt-10 md:py-10"
                role="timer"
                aria-label="Hitung mundur menuju race day"
                data-countdown="{{ $raceDay->toIso8601String() }}"
            >
                <div class="absolute -left-16 -top-20 -z-10 size-56 rounded-full bg-secondary/40 blur-3xl" aria-hidden="true"></div>
                <div class="absolute -bottom-24 -right-10 -z-10 size-64 rounded-full bg-accent-cyan/35 blur-3xl" aria-hidden="true"></div>

                <div class="flex items-start justify-center">
                    @foreach (['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Minutes', 'seconds' => 'Seconds'] as $unit => $label)
                        <div class="flex min-w-0 flex-1 flex-col items-center sm:max-w-28">
                            <span class="text-h2 tabular-nums sm:text-h1" data-countdown-unit="{{ $unit }}">{{ sprintf('%02d', $timeLeft[$unit]) }}</span>
                            <span class="mt-1 text-caption font-medium text-white/80 sm:text-body-sm">{{ $label }}</span>
                        </div>
                        @unless ($loop->last)
                            <span class="hidden text-h1 text-white/45 sm:block" aria-hidden="true">:</span>
                        @endunless
                    @endforeach
                </div>
            </div>

            <div class="mt-8 text-center md:mt-10">
                <h2 class="text-h3 uppercase">See You on Race Day!</h2>
                <p class="mt-2 text-body text-muted">{{ $raceDay->translatedFormat('l, j F Y') }} &middot; UC Color Run 2027</p>
                <x-button :href="route('registration.type')" class="mt-5">Register Now</x-button>
            </div>
        </x-panel>
    </div>
</x-layouts.app>

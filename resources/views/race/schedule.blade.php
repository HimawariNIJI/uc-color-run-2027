<x-layouts.app title="Race Schedule" description="Hitung mundur menuju race day UC Color Run 2027.">
    <div class="page-container page-section">
        <x-panel>
            <x-panel-heading
                title="Race Schedule"
                :divider="false"
                description="Hitung mundur menuju hari perlombaan UC Color Run 2027. Siapkan dirimu, atur latihan, dan jangan sampai ketinggalan!"
            />

            {{-- Countdown on a colour-run landscape --}}
            <div
                class="bg-powder relative isolate mx-auto mt-8 max-w-3xl overflow-hidden rounded-3xl bg-primary px-3 pb-24 pt-8 shadow-card sm:px-8 sm:pb-32 md:mt-10 md:pt-10"
                role="timer"
                aria-label="Hitung mundur menuju race day"
                data-countdown="{{ $raceDay->toIso8601String() }}"
                data-countdown-animate
            >
                <x-brand-image file="2@3x.png" eager class="absolute -left-16 top-2 -z-10 w-44 opacity-70 motion-safe:animate-float-slow sm:w-64" />
                <x-brand-image file="3@3x.png" eager class="absolute -right-14 top-10 -z-10 w-40 opacity-70 motion-safe:animate-float sm:w-60" />
                <x-brand-image file="Asset 17@3x.png" eager class="absolute -bottom-1 left-1/2 -z-10 w-[140%] -translate-x-1/2 sm:w-[115%]" />
                <x-brand-image file="Asset 13@3x.png" eager class="absolute -bottom-2 left-[4%] -z-10 w-10 sm:w-14" />
                <x-brand-image file="Asset 11@3x.png" eager class="absolute -bottom-2 left-[11%] -z-10 w-7 sm:w-10" />
                <x-brand-image file="Asset 12@3x.png" eager class="absolute -bottom-2 right-[6%] -z-10 w-11 sm:w-16" />

                <div class="mx-auto grid max-w-2xl grid-cols-2 gap-3 min-[25rem]:grid-cols-4 sm:gap-5">
                    @foreach (['days' => 'Days', 'hours' => 'Hours', 'minutes' => 'Minutes', 'seconds' => 'Seconds'] as $unit => $label)
                        <x-countdown-tile
                            :unit="$unit"
                            :value="$timeLeft[$unit]"
                            :label="$label"
                            class="motion-safe:animate-rise"
                            style="animation-delay: {{ $loop->index * 90 }}ms"
                        />
                    @endforeach
                </div>
            </div>

            <div class="mt-8 flex flex-col items-center text-center md:mt-10" data-reveal>
                <h2 class="font-display text-h3 uppercase text-primary">See You on Race Day!</h2>
                <p class="mt-2 text-body text-muted">{{ $raceDay->translatedFormat('l, j F Y') }} &middot; UC Color Run 2027</p>
                <x-button :href="route('registration.type')" class="mt-5">
                    Register Now
                    <x-icon name="arrow-right" class="size-4.5" />
                </x-button>
            </div>
        </x-panel>
    </div>
</x-layouts.app>

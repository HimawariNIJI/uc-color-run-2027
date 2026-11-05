@use('App\Enums\FeePeriod')
@use('App\Support\Rupiah')

<x-layouts.app title="Pilih Tipe Harga" description="Pilih harga Early Bird atau Normal Price untuk registrasi UC Color Run 2027.">
    <div class="page-container page-section">
        <x-panel>
            <x-registration-steps :current="2" />
            <h1 class="mt-6 text-center font-display text-h3 font-normal uppercase text-primary">Pilih Tipe Harga</h1>
            <p class="mt-2 text-center text-body-sm text-muted">
                Tipe registrasi: <span class="font-semibold text-foreground">{{ $registrationType->label() }}</span>
                &middot;
                <a href="{{ route('registration.type') }}" class="font-semibold text-primary hover:underline">Ganti</a>
            </p>

            <div class="mt-6 flex flex-col gap-4 md:mt-8 md:gap-5">
                @foreach ($priceTypes as $option)
                    @php($period = $option['period'])
                    <x-choice-card
                        :href="route('registration.category', [$registrationType, $period->slug()])"
                        :title="$period->label()"
                        :description="$period->description()"
                        :icon="$period->icon()"
                        :meta="Rupiah::format($option['fees']->min()).' – '.Rupiah::format($option['fees']->max())"
                        :status="$period === FeePeriod::EarlyBird ? ($option['isAvailable'] ? 'Slot terbatas' : 'Sold out') : null"
                        :disabled="! $option['isAvailable']"
                    />
                @endforeach
            </div>

            <p class="mt-6 text-center text-body-sm text-muted">Harga akhir mengikuti jarak lari yang kamu pilih di langkah berikutnya.</p>
        </x-panel>
    </div>
</x-layouts.app>

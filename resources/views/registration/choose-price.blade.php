<x-layouts.app title="Pilih Tipe Harga" description="Pilih paket Early Bid atau Normal Bid untuk registrasi UC Color Run 2027.">
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
                    @php
                        $period = $option['period'];
                        $packages = $option['packages'];
                        $prices = $packages->pluck('price');
                        $isSoldOut = $packages->every->isSoldOut();
                        $meta = $prices->contains(null)
                            ? 'Price To Be Announced'
                            : \App\Support\Rupiah::format($prices->min()).($prices->min() === $prices->max() ? '' : ' – '.\App\Support\Rupiah::format($prices->max()));
                        $status = match (true) {
                            $isSoldOut => 'Sold out',
                            $packages->contains->isOpen() && $packages->every->hasUnlimitedQuota() => 'Kuota tidak terbatas',
                            $packages->contains->isOpen() => 'Kuota terbatas',
                            default => 'Belum dibuka',
                        };
                    @endphp
                    <x-choice-card
                        :href="route('registration.category', [$registrationType, $period->slug()])"
                        :title="$period->label()"
                        :description="$period->description()"
                        :icon="$period->icon()"
                        :meta="$meta"
                        :status="$status"
                        :disabled="$isSoldOut"
                    />
                @endforeach
            </div>

            <p class="mt-6 text-center text-body-sm text-muted">Harga dan kuota mengikuti jarak lari yang kamu pilih di langkah berikutnya.</p>
        </x-panel>
    </div>
</x-layouts.app>

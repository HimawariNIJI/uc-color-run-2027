<x-layouts.app title="Registration" description="Pilih tipe registrasi UC Color Run 2027: Internal, External, atau Bundling.">
    <div class="page-container page-section">
        <x-panel>
            <x-registration-steps :current="1" />
            <h1 class="mt-6 text-center font-display text-h3 font-normal uppercase text-primary">Pilih Tipe Registrasi</h1>

            <div class="mt-6 flex flex-col gap-4 md:mt-8 md:gap-5">
                @foreach ($registrationTypes as $type)
                    <x-choice-card
                        :href="route('registration.price', $type)"
                        :title="$type->label()"
                        :description="$type->description()"
                        :icon="$type->icon()"
                    />
                @endforeach
            </div>
        </x-panel>
    </div>
</x-layouts.app>

<x-layouts.app title="About" description="Kenali UC Color Run 2027, latar belakang, dan nilai-nilai yang kami bawa.">
    <div class="page-container page-section">
        <x-panel>
            <div class="flex flex-col gap-10 md:gap-14">
                <div class="grid items-center gap-6 md:grid-cols-2 md:gap-10">
                    <div>
                        <h1 class="text-h2 uppercase">Apa itu UC Color Run?</h1>
                        <p class="mt-4 text-body text-muted">
                            UC Color Run adalah acara lari tahunan Universitas Ciputra yang memadukan olahraga, musik, dan perayaan warna. Di setiap color zone, peserta disambut lemparan bubuk warna yang aman sehingga garis finish dipenuhi pelari yang berwarna-warni. Acara ini terbuka untuk mahasiswa, alumni, keluarga, hingga masyarakat umum.
                        </p>
                    </div>
                    <x-media class="aspect-[4/3]" tone="primary" alt="Dokumentasi UC Color Run" label="Foto UC Color Run" />
                </div>

                <div class="grid items-center gap-6 md:grid-cols-2 md:gap-10">
                    <div class="md:order-last md:text-right">
                        <h2 class="text-h2 uppercase">Latar Belakang</h2>
                        <p class="mt-4 text-body text-muted">
                            Berawal dari keinginan untuk mendekatkan kampus dengan masyarakat, UC Color Run hadir sebagai ruang bertemu yang sehat dan menyenangkan. Sejalan dengan semangat entrepreneurship Universitas Ciputra, acara ini dikelola oleh mahasiswa sebagai wadah belajar merancang dan menjalankan event berskala besar.
                        </p>
                    </div>
                    <x-media class="aspect-[4/3]" tone="pink" alt="Suasana UC Color Run" label="Foto suasana acara" />
                </div>
            </div>
        </x-panel>

        <section class="mt-16 border-t border-border pt-12 md:mt-20 md:pt-16">
            <x-section-heading eyebrow="Our Values" lined title="UC Color Run" />

            <ul class="mx-auto mt-10 grid max-w-content grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                @foreach ($values as $value)
                    <li class="flex flex-col items-center text-center">
                        <x-media class="aspect-square w-full max-w-44" :icon="$value['icon']" tone="primary" />
                        <h3 class="mt-5 text-h4 uppercase">{{ $value['title'] }}</h3>
                        <p class="mt-2 text-body-sm text-muted">{{ $value['description'] }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="mt-16 border-t border-border pt-12 md:mt-20 md:pt-16">
            <div class="mx-auto grid max-w-content overflow-hidden rounded-3xl border border-border bg-surface shadow-card md:grid-cols-[2fr_3fr]">
                <x-media class="aspect-video rounded-none md:aspect-auto md:min-h-72" tone="cyan" icon="users" alt="Peserta UC Color Run" />
                <div class="flex flex-col gap-3 border-t border-border p-6 sm:p-8 md:border-l md:border-t-0 md:p-10">
                    <p class="text-caption font-semibold uppercase tracking-[0.18em] text-primary">Come and Join Us</p>
                    <h2 class="text-h2 uppercase">UC Color Run</h2>
                    <p class="text-body text-muted">
                        Jadilah bagian dari lautan warna di UC Color Run 2027. Ajak teman, keluarga, dan komunitasmu untuk berlari bersama.
                    </p>
                    <x-button :href="route('registration.type')" class="mt-2 self-start md:self-end">Register Now</x-button>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>

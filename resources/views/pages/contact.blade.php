<x-layouts.app title="Contact" description="Hubungi panitia UC Color Run 2027 untuk pertanyaan seputar registrasi, pembayaran, dan hari lomba.">
    <div class="page-container page-section">
        <x-panel>
            <div class="grid gap-10 lg:grid-cols-2 lg:gap-12">
                <div>
                    <h1 class="text-h2 text-primary">Contact Us</h1>
                    <p class="mt-3 text-body text-muted">
                        Punya pertanyaan seputar registrasi, pembayaran, atau hari lomba? Kirim pesan dan panitia akan membalas melalui email.
                    </p>

                    @auth
                        <form method="POST" action="{{ route('contact.store') }}" class="mt-6 flex flex-col gap-4" novalidate data-submit-guard>
                            @csrf

                            <x-form.field label="Full Name" for="name">
                                <x-form.input name="name" :value="auth()->user()->name" placeholder="Enter your name" autocomplete="name" pill required />
                            </x-form.field>

                            {{-- Shown for reference only: it has no name, so it is never submitted. The server uses the account email. --}}
                            <x-form.field label="Email Address" for="email" hint="Diambil dari akun Google kamu dan tidak dapat diubah.">
                                <input type="email" id="email" value="{{ auth()->user()->email }}" readonly aria-describedby="email-hint" class="form-control h-12 cursor-default rounded-full px-5 text-muted">
                            </x-form.field>

                            <x-form.field label="Message" for="message">
                                <x-form.textarea name="message" rows="5" placeholder="How can we help you?" required />
                            </x-form.field>

                            <x-button type="submit" pill block class="mt-1" data-busy-label="Mengirim…">Submit</x-button>
                        </form>
                    @else
                        <div class="mt-6 flex flex-col gap-3">
                            <x-google-sign-in-button :return-to="route('contact', absolute: false)" />
                            <p class="text-center text-caption text-muted">Masuk dengan akun Google untuk mengirim pesan. Setelah masuk, kamu akan kembali ke halaman ini.</p>
                        </div>
                    @endauth
                </div>

                <x-media
                    class="hidden min-h-96 lg:block"
                    :src="\App\Support\BrandAsset::url('Contact Us Page.JPG')"
                    alt="Panitia UC Color Run berpose di depan backdrop acara"
                />
            </div>
        </x-panel>

        <section class="mt-16 border-t border-border pt-12 md:mt-20 md:pt-16">
            <x-section-heading title="More Ways to Connect" :uppercase="false" description="Kamu juga dapat menghubungi panitia melalui kanal resmi berikut atau langsung datang ke sekretariat kami." />

            <ul class="mx-auto mt-10 grid max-w-content gap-x-4 gap-y-8 min-[30rem]:grid-cols-2 lg:grid-cols-4">
                @foreach ($channels as $channel)
                    <li>
                        <a
                            href="{{ $channel['url'] }}"
                            @if ($channel['external']) target="_blank" rel="noopener" @endif
                            class="group flex flex-col items-center gap-3 rounded-2xl p-2 text-center focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        >
                            <span class="grid size-16 place-items-center rounded-full bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-white md:size-20">
                                <x-icon :name="$channel['icon']" class="size-7" />
                            </span>
                            <span class="text-body font-semibold">{{ $channel['label'] }}</span>
                            <span class="text-body-sm wrap-anywhere text-muted group-hover:text-primary">{{ $channel['value'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</x-layouts.app>

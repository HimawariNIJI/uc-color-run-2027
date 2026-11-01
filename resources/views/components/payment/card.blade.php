{{--
    Narrow card shell shared by the payment pages: logo header, divider, content, help line.
--}}
@props(['eyebrow' => 'Registration Payment', 'help' => true])

<div class="page-container page-section">
    <section {{ $attributes->class('mx-auto w-full max-w-card rounded-3xl border border-border bg-surface p-5 shadow-card sm:p-6') }}>
        <header class="flex flex-col items-center gap-3 border-b border-border pb-5 text-center">
            <x-logo size="lg" />
            <p class="text-caption font-semibold uppercase tracking-[0.2em] text-muted">{{ $eyebrow }}</p>
        </header>

        <div class="mt-5 flex flex-col gap-4">
            {{ $slot }}
        </div>
    </section>

    @if ($help)
        <p class="mt-5 text-center text-caption text-muted">
            Butuh bantuan? <a href="{{ route('contact') }}" class="font-semibold text-primary hover:underline">Hubungi panitia UC Color Run.</a>
        </p>
    @endif
</div>

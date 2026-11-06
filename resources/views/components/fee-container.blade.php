{{--
    Fee Container from the Design Division, built in code: an indigo header over a cream body
    framed in indigo, listing the price for each registration type.

    prices:      [label => amount in Rupiah]
    status:      optional pill under the title, for example "Sisa 120 slot" or "Sold out"
    unavailable: dims the status pill (sold out)
--}}
@props(['title', 'prices', 'status' => null, 'unavailable' => false, 'headingLevel' => 'h3'])

<article {{ $attributes->class('group/fee flex min-w-0 flex-col overflow-hidden rounded-[1.75rem] border-[3px] border-primary bg-brand-cream transition duration-300 ease-spring hover:-translate-y-1 hover:shadow-[0_8px_0_0_var(--color-brand-orange)] motion-reduce:transform-none') }}>
    <header class="bg-primary px-5 pb-4 pt-5 text-center">
        <{{ $headingLevel }} class="font-display text-h3 font-normal uppercase tracking-wide text-brand-yellow">{{ $title }}</{{ $headingLevel }}>
        @if ($status)
            <span @class([
                'mt-2 inline-flex rounded-full px-3 py-1 text-caption font-semibold',
                'bg-brand-cream/15 text-brand-cream' => $unavailable,
                'bg-brand-yellow text-primary' => ! $unavailable,
            ])>{{ $status }}</span>
        @endif
    </header>

    <dl class="flex flex-1 flex-col divide-y divide-primary/10 px-5 py-2">
        @foreach ($prices as $label => $amount)
            <div class="flex items-baseline justify-between gap-3 py-3">
                <dt class="text-body-sm font-medium text-muted">{{ $label }}</dt>
                <dd class="font-display text-h4 text-primary tabular-nums">{{ \App\Support\Rupiah::format($amount) }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($slot->isNotEmpty())
        <div class="border-t border-primary/10 px-5 py-3 text-caption text-muted">{{ $slot }}</div>
    @endif
</article>

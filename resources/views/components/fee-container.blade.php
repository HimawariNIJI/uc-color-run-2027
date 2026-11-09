{{--
    Fee Container from the Design Division, built in code: an indigo header over a cream body
    framed in indigo, listing one row per registration package with its quota and price.

    packages: TicketPrice models (a price of null shows "Price To Be Announced")
--}}
@props(['title', 'packages', 'headingLevel' => 'h3'])

<article {{ $attributes->class('group/fee flex min-w-0 flex-col overflow-hidden rounded-[1.75rem] border-[3px] border-primary bg-brand-cream transition duration-300 ease-spring hover:-translate-y-1 hover:shadow-[0_8px_0_0_var(--color-brand-orange)] motion-reduce:transform-none') }}>
    <header class="bg-primary px-5 pb-4 pt-5 text-center">
        <{{ $headingLevel }} class="font-display text-h3 font-normal uppercase tracking-wide text-brand-yellow">{{ $title }}</{{ $headingLevel }}>
    </header>

    <dl class="flex flex-1 flex-col divide-y divide-primary/10 px-5 py-2">
        @foreach ($packages as $package)
            {{-- In narrow containers the price moves under the label instead of squeezing it. --}}
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 py-3">
                <dt class="min-w-0 flex-1 basis-24">
                    <span class="block text-body-sm font-medium text-foreground">{{ $package->eligibility() }}</span>
                    <span @class(['block text-caption', 'text-error' => $package->isSoldOut(), 'text-muted' => ! $package->isSoldOut()])>{{ $package->quotaLabel() }}</span>
                </dt>
                <dd @class([
                    'ml-auto shrink-0 text-right',
                    'font-display text-h4 text-primary tabular-nums' => $package->price !== null,
                    'text-caption font-semibold text-secondary-strong' => $package->price === null,
                ])>{{ $package->priceLabel() }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($slot->isNotEmpty())
        <div class="border-t border-primary/10 px-5 py-3 text-caption text-muted">{{ $slot }}</div>
    @endif
</article>

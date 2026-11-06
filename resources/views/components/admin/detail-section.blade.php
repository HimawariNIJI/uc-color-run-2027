@props(['title'])

<section {{ $attributes->class('border-t border-border pt-6 first:border-t-0 first:pt-0') }}>
    <h3 class="text-body font-bold">{{ $title }}</h3>
    <dl class="mt-4 grid gap-x-6 gap-y-4 min-[26rem]:grid-cols-2">
        {{ $slot }}
    </dl>
</section>

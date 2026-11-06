{{--
    A label/value line inside summary cards. Use inside a <dl>.
    emphasis: renders the value large and bold (totals).
--}}
@props(['label', 'emphasis' => false])

<div {{ $attributes->class('flex items-center justify-between gap-4') }}>
    <dt class="{{ $emphasis ? 'text-body text-foreground' : 'text-body-sm text-muted' }}">{{ $label }}</dt>
    <dd class="min-w-0 text-right break-words {{ $emphasis ? 'text-h4 font-bold' : 'text-body-sm font-semibold' }}">{{ $slot }}</dd>
</div>

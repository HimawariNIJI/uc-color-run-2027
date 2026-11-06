{{-- Label/value pair inside a <dl>. --}}
@props(['label'])

<div {{ $attributes->class('min-w-0') }}>
    <dt class="text-body-sm text-muted">{{ $label }}</dt>
    <dd class="mt-0.5 text-body font-medium wrap-anywhere">{{ $slot }}</dd>
</div>

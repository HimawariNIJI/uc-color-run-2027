{{--
    The signed-in admin: initial in a circle plus their name.

    size: "md" (header) | "lg" (mobile account menu)
--}}
@props(['admin', 'size' => 'md'])

<span {{ $attributes->class([
    'inline-flex min-w-0 items-center rounded-full border border-border bg-surface',
    $size === 'lg' ? 'gap-3 py-1.5 pl-1.5 pr-6' : 'gap-2.5 py-1 pl-1 pr-4',
]) }}>
    <span @class([
        'grid shrink-0 place-items-center rounded-full bg-primary/10 font-bold text-primary',
        $size === 'lg' ? 'size-11 text-h4' : 'size-8 text-body-sm',
    ]) aria-hidden="true">{{ $admin->initial() }}</span>
    <span @class(['truncate font-medium', $size === 'lg' ? 'text-body-lg' : 'text-body-sm'])>{{ $admin->name }}</span>
</span>

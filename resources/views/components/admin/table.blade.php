{{--
    Data table that scrolls sideways on narrow screens instead of squeezing its columns.
    Put the <th> cells in the "head" slot and the <tr> rows in the default slot.

    label:    accessible name for the scroll region and table caption
    minWidth: Tailwind min-width class; below it the table scrolls
    bodyId:   id for the <tbody>, when script adds rows to it
--}}
@props(['label', 'minWidth' => 'min-w-[40rem]', 'bodyId' => null])

<div
    {{ $attributes->class('relative -mx-5 overflow-x-auto px-5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:mx-0 sm:px-0') }}
    role="region"
    aria-label="{{ $label }}"
    tabindex="0"
>
    <table @class([
        'w-full border-separate border-spacing-0 text-left text-body-sm',
        '[&_th]:bg-primary/6 [&_th]:px-4 [&_th]:py-3 [&_th]:text-caption [&_th]:font-semibold [&_th]:uppercase [&_th]:tracking-wide [&_th]:whitespace-nowrap [&_th]:text-foreground/70',
        '[&_th:first-child]:rounded-l-xl [&_th:last-child]:rounded-r-xl',
        '[&_td]:border-b [&_td]:border-border [&_td]:px-4 [&_td]:py-3.5 [&_td]:align-middle',
        $minWidth,
    ])>
        <caption class="sr-only">{{ $label }}</caption>
        <thead>
            <tr>{{ $head }}</tr>
        </thead>
        <tbody @if ($bodyId) id="{{ $bodyId }}" @endif>
            {{ $slot }}
        </tbody>
    </table>
</div>

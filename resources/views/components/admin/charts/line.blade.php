{{--
    Single-series line chart over time: 2px line on a light wash, hairline gridlines, the
    latest value labelled at the end, and a crosshair tooltip on hover, touch and arrow keys.

    points: list of ['date' => CarbonInterface, 'value' => int]
--}}
@props(['points', 'label', 'valueHeading', 'unit'])

@use('App\Support\NumberFormat')

@php
    $points = collect($points)->values();
    $count = $points->count();
    $lastIndex = max(0, $count - 1);
    $maxValue = (int) $points->max('value');

    // Clean y-axis steps of 1, 2 or 5 × 10ⁿ, about four ticks.
    $rawStep = max(1, $maxValue / 4);
    $magnitude = 10 ** floor(log10($rawStep));
    $step = (int) (collect([1, 2, 5, 10])->first(fn (int $factor): bool => $factor * $magnitude >= $rawStep) * $magnitude);
    $yMax = max($step, (int) (ceil($maxValue / $step) * $step));
    $ticks = range(0, $yMax, $step);

    $x = fn (int $index): float => $count > 1 ? round($index / $lastIndex * 100, 3) : 50;
    $y = fn (int $value): float => round(100 - $value / $yMax * 100, 3);

    $line = $points->map(fn (array $point, int $index): string => ($index === 0 ? 'M' : 'L').$x($index).' '.$y($point['value']))->implode(' ');
    $area = $line.' L'.$x($lastIndex).' 100 L'.$x(0).' 100 Z';

    // Date labels: every day for a week, otherwise every 5th / 15th day, always including the last.
    $every = $count <= 7 ? 1 : ($count <= 31 ? 5 : 15);
    $labelled = collect(range(0, $lastIndex, $every));
    if ($labelled->last() !== $lastIndex) {
        $labelled = $lastIndex - $labelled->last() < $every / 2 ? $labelled->slice(0, -1)->push($lastIndex) : $labelled->push($lastIndex);
    }
    $labelled = $labelled->values();

    $last = $points->last();
    $total = $points->sum('value');

    // Read by the crosshair script.
    $pointsJson = $points->map(fn (array $point): array => [
        'label' => $point['date']->translatedFormat('l, j M Y'),
        'value' => $point['value'],
        'formatted' => NumberFormat::integer($point['value']),
    ])->toJson();
@endphp

<figure {{ $attributes->class('min-w-0') }}>
    <div class="grid grid-cols-[2rem_minmax(0,1fr)] gap-x-3">
        <div class="relative h-52 text-right text-caption text-muted tabular-nums sm:h-60" aria-hidden="true">
            @foreach ($ticks as $tick)
                <span class="absolute right-0 -translate-y-1/2 leading-none" style="top: {{ $y($tick) }}%">{{ NumberFormat::integer($tick) }}</span>
            @endforeach
        </div>

        <div
            class="relative h-52 touch-pan-y rounded-sm outline-offset-4 focus-visible:outline-2 focus-visible:outline-primary sm:h-60"
            role="img"
            aria-label="{{ $label }}: {{ NumberFormat::integer($total) }} {{ $unit }} dalam {{ $count }} hari, {{ NumberFormat::integer($last['value'] ?? 0) }} pada {{ $last['date']->translatedFormat('j F Y') }}."
            tabindex="0"
            data-line-chart
            data-y-max="{{ $yMax }}"
            data-unit="{{ $unit }}"
            data-points="{{ $pointsJson }}"
        >
            @foreach ($ticks as $tick)
                <span @class(['absolute inset-x-0 border-t', 'border-foreground/15' => $tick === 0, 'border-border' => $tick !== 0]) style="top: {{ $y($tick) }}%" aria-hidden="true"></span>
            @endforeach

            <svg class="absolute inset-0 size-full overflow-visible" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                <path d="{{ $area }}" class="fill-primary/10" />
                <path d="{{ $line }}" fill="none" class="stroke-primary" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            </svg>

            {{-- Latest value, labelled at the end of the line. --}}
            <span class="absolute size-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary ring-2 ring-surface" style="left: {{ $x($lastIndex) }}%; top: {{ $y($last['value'] ?? 0) }}%" aria-hidden="true"></span>
            <span class="absolute -translate-x-full -translate-y-[calc(100%+0.625rem)] whitespace-nowrap rounded-md bg-surface/90 px-1 text-body-sm font-semibold" style="left: {{ $x($lastIndex) }}%; top: {{ $y($last['value'] ?? 0) }}%" aria-hidden="true" data-line-chart-end-label>{{ NumberFormat::integer($last['value'] ?? 0) }}</span>

            <span class="pointer-events-none absolute inset-y-0 w-px -translate-x-1/2 bg-foreground/25" hidden data-line-chart-crosshair></span>
            <span class="pointer-events-none absolute size-3 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary ring-2 ring-surface" hidden data-line-chart-dot></span>
            <div class="pointer-events-none absolute top-0 z-10 min-w-36 rounded-xl border border-border bg-surface px-3 py-2 shadow-popover" hidden data-line-chart-tooltip>
                <p class="flex items-center gap-2 text-body-sm font-semibold">
                    <span class="h-0.5 w-3 rounded-full bg-primary"></span>
                    <span data-line-chart-value></span>
                </p>
                <p class="mt-0.5 whitespace-nowrap text-caption text-muted" data-line-chart-label></p>
            </div>
        </div>

        <div></div>
        <div class="relative mt-2 h-4 text-caption text-muted" aria-hidden="true">
            @foreach ($labelled as $position => $index)
                <span
                    @class([
                        'absolute whitespace-nowrap leading-none',
                        '-translate-x-1/2' => $index !== 0 && $index !== $lastIndex,
                        '-translate-x-full' => $index === $lastIndex && $index !== 0,
                        'max-sm:hidden' => $position % 2 === 1 && $index !== $lastIndex,
                    ])
                    style="left: {{ $x($index) }}%"
                >{{ $points[$index]['date']->translatedFormat('j M') }}</span>
            @endforeach
        </div>
    </div>

    <table class="sr-only">
        <caption>{{ $label }}</caption>
        <thead>
            <tr><th scope="col">Tanggal</th><th scope="col">{{ $valueHeading }}</th></tr>
        </thead>
        <tbody>
            @foreach ($points as $point)
                <tr><th scope="row">{{ $point['date']->translatedFormat('j F Y') }}</th><td>{{ NumberFormat::integer($point['value']) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</figure>

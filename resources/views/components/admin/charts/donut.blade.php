{{--
    Part-to-whole ring with the total in the middle and a legend that prints every figure,
    so no value depends on colour or hover alone.

    data: list of ['label' => string, 'value' => int, 'tone' => 'success'|'warning'|'error']
--}}
@props(['data', 'label', 'centerLabel', 'categoryHeading', 'valueHeading'])

@use('App\Support\NumberFormat')

@php
    // Status hues validated for colour-vision deficiency (darker green keeps it apart from red).
    $tones = [
        'success' => ['stroke' => 'stroke-success-strong', 'swatch' => 'bg-success-strong', 'color' => 'var(--color-success-strong)'],
        'warning' => ['stroke' => 'stroke-warning', 'swatch' => 'bg-warning', 'color' => 'var(--color-warning)'],
        'error' => ['stroke' => 'stroke-error', 'swatch' => 'bg-error', 'color' => 'var(--color-error)'],
    ];

    $rows = collect($data);
    $total = $rows->sum('value');
    $radius = 42;
    $circumference = 2 * M_PI * $radius;
    $gap = $rows->where('value', '>', 0)->count() > 1 ? 1.2 : 0;

    $offset = 0;
    $segments = $rows->map(function (array $row) use ($total, $circumference, $gap, &$offset): array {
        $length = $total > 0 ? $row['value'] / $total * $circumference : 0;
        $segment = [...$row, 'dash' => max(0, $length - $gap), 'offset' => $offset];
        $offset += $length;

        return $segment;
    });
@endphp

<figure {{ $attributes->class('flex flex-wrap items-center justify-center gap-x-10 gap-y-6') }}>
    <div class="relative size-44 shrink-0 sm:size-48">
        <svg viewBox="0 0 100 100" class="size-full -rotate-90" aria-hidden="true">
            @if ($total === 0)
                <circle cx="50" cy="50" r="{{ $radius }}" fill="none" stroke-width="12" class="stroke-foreground/6" />
            @endif
            @foreach ($segments as $segment)
                @continue($segment['value'] === 0)
                <circle
                    cx="50" cy="50" r="{{ $radius }}" fill="none" stroke-width="12"
                    stroke-dasharray="{{ round($segment['dash'], 3) }} {{ round($circumference, 3) }}"
                    stroke-dashoffset="{{ round(-$segment['offset'], 3) }}"
                    class="{{ $tones[$segment['tone']]['stroke'] }} transition-opacity duration-150 hover:opacity-80"
                    data-chart-tip-value="{{ NumberFormat::integer($segment['value']) }} {{ $valueHeading }}"
                    data-chart-tip-label="{{ $segment['label'] }} · {{ NumberFormat::percentage($segment['value'], $total) }}"
                    data-chart-tip-color="{{ $tones[$segment['tone']]['color'] }}"
                    data-chart-tip-target
                />
            @endforeach
        </svg>
        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center" aria-hidden="true">
            <span class="text-h3 font-bold">{{ NumberFormat::integer($total) }}</span>
            <span class="text-caption text-muted">{{ $centerLabel }}</span>
        </div>
    </div>

    <ul class="flex w-full max-w-60 flex-col gap-3 sm:w-auto sm:min-w-52" aria-hidden="true">
        @foreach ($rows as $row)
            <li class="flex items-center gap-3 text-body-sm">
                <span class="size-3 shrink-0 rounded-[3px] {{ $tones[$row['tone']]['swatch'] }}"></span>
                <span class="flex-1">{{ $row['label'] }}</span>
                <span class="font-semibold tabular-nums">{{ NumberFormat::integer($row['value']) }}</span>
                <span class="w-12 text-right text-caption text-muted tabular-nums">{{ NumberFormat::percentage($row['value'], $total) }}</span>
            </li>
        @endforeach
    </ul>

    <table class="sr-only">
        <caption>{{ $label }}</caption>
        <thead>
            <tr><th scope="col">{{ $categoryHeading }}</th><th scope="col">{{ $valueHeading }}</th><th scope="col">%</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr><th scope="row">{{ $row['label'] }}</th><td>{{ NumberFormat::integer($row['value']) }}</td><td>{{ NumberFormat::percentage($row['value'], $total) }}</td></tr>
            @endforeach
        </tbody>
    </table>
</figure>

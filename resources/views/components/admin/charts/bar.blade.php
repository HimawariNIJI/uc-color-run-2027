{{--
    Horizontal bar chart for a single series: one hue, thin bars from a shared baseline,
    each value printed at the bar's tip.

    data: list of ['label' => string, 'value' => int]
--}}
@props(['data', 'label', 'categoryHeading', 'valueHeading'])

@use('App\Support\NumberFormat')

@php
    $rows = collect($data);
    $max = max(1, $rows->max('value'));
    $total = $rows->sum('value');
@endphp

<figure {{ $attributes->class('min-w-0') }}>
    <div class="grid grid-cols-[auto_minmax(0,1fr)] items-stretch" aria-hidden="true">
        @foreach ($rows as $row)
            @php($share = NumberFormat::percentage($row['value'], $total))
            <div
                class="contents"
                data-chart-tip-value="{{ NumberFormat::integer($row['value']) }} {{ $valueHeading }}"
                data-chart-tip-label="{{ $row['label'] }} · {{ $share }}"
                data-chart-tip-color="var(--color-primary)"
            >
                <span class="flex items-center pr-3 text-body-sm font-semibold">{{ $row['label'] }}</span>
                <span class="flex min-w-0 items-center gap-2 border-l border-border py-3" data-chart-tip-target>
                    <span class="h-6 shrink-0 rounded-r-sm bg-primary transition-[filter] duration-150 hover:brightness-110" style="width: calc((100% - 5.5rem) * {{ round($row['value'] / $max, 4) }})"></span>
                    <span class="whitespace-nowrap text-body-sm">
                        <span class="font-semibold">{{ NumberFormat::integer($row['value']) }}</span>
                        <span class="text-muted">({{ $share }})</span>
                    </span>
                </span>
            </div>
        @endforeach
    </div>

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

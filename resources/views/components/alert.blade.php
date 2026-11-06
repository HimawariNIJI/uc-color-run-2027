{{--
    tone: "success" | "error" | "warning" | "info"
--}}
@props(['tone' => 'info', 'title' => null, 'dismissible' => false])

@php
    [$toneClasses, $icon] = match ($tone) {
        'success' => ['border-success/25 bg-success/8 text-success-strong', 'circle-check'],
        'error' => ['border-error/25 bg-error/6 text-error', 'circle-alert'],
        'warning' => ['border-warning/35 bg-warning/10 text-warning-strong', 'clock'],
        default => ['border-info/20 bg-info/6 text-info', 'info'],
    };
@endphp

<div
    {{ $attributes->class(['flex items-start gap-3 rounded-xl border px-4 py-3', $toneClasses]) }}
    role="{{ in_array($tone, ['error', 'warning'], true) ? 'alert' : 'status' }}"
    @if ($dismissible) data-dismissible @endif
>
    <x-icon :name="$icon" class="mt-0.5 size-4.5" />
    <div class="min-w-0 flex-1 text-body-sm">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="{{ $title ? 'mt-0.5 text-foreground/80' : 'font-medium' }}">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" class="-m-1.5 grid size-8 shrink-0 place-items-center rounded-lg opacity-70 transition hover:bg-foreground/5 hover:opacity-100" aria-label="Tutup pesan" data-dismiss>
            <x-icon name="x" class="size-4" />
        </button>
    @endif
</div>

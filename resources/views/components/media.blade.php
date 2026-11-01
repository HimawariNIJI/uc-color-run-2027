{{--
    An image slot. Shows the image when src is set, otherwise a branded placeholder,
    so real photos can be dropped in later without touching the layout.
    Give it a size or aspect ratio from the caller, e.g. class="aspect-[4/3]".
    Slot content is centered on top of the image or placeholder.

    tone: "primary" | "pink" | "yellow" | "cyan" (placeholder colour)
    icon: placeholder icon name, or null for none
--}}
@props(['src' => null, 'alt' => '', 'icon' => 'image', 'label' => null, 'tone' => 'primary'])

@php
    [$surface, $blobA, $blobB, $iconColor] = match ($tone) {
        'pink' => ['bg-secondary/10', 'bg-secondary/30', 'bg-primary/20', 'text-secondary'],
        'yellow' => ['bg-accent-yellow/20', 'bg-accent-yellow/50', 'bg-secondary/20', 'text-warning-strong'],
        'cyan' => ['bg-accent-cyan/12', 'bg-accent-cyan/35', 'bg-primary/15', 'text-accent-cyan-strong'],
        default => ['bg-primary/8', 'bg-primary/25', 'bg-accent-cyan/25', 'text-primary'],
    };
@endphp

<div {{ $attributes->class(['relative isolate overflow-hidden rounded-2xl', $src ? 'bg-border' : $surface]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" class="absolute inset-0 -z-10 size-full object-cover" loading="lazy" decoding="async">
    @else
        <div class="absolute -left-[12%] -top-[20%] -z-10 aspect-square w-[55%] rounded-full blur-2xl {{ $blobA }}" aria-hidden="true"></div>
        <div class="absolute -bottom-[25%] -right-[10%] -z-10 aspect-square w-[60%] rounded-full blur-2xl {{ $blobB }}" aria-hidden="true"></div>
    @endif

    <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-4 text-center" @if (! $src && $alt) role="img" aria-label="{{ $alt }}" @endif>
        @if (! $src && $icon)
            <span class="grid size-12 place-items-center rounded-2xl bg-surface/80 shadow-sm backdrop-blur {{ $iconColor }} md:size-14">
                <x-icon :name="$icon" class="size-6 md:size-7" />
            </span>
        @endif
        @if (! $src && $label)
            <span class="text-caption font-medium text-foreground/60">{{ $label }}</span>
        @endif
        {{ $slot }}
    </div>
</div>

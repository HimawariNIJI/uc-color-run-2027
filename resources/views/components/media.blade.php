{{--
    An image slot. Shows the image when src is set, otherwise a branded placeholder
    (soft colour-run glow with a mountain silhouette), so real photos can be dropped in later
    without touching the layout. Give it a size or aspect ratio from the caller, e.g. class="aspect-[4/3]".
    Slot content is centered on top of the image or placeholder.

    tone: "primary" | "pink" | "yellow" | "violet" (placeholder colour; "cyan" is kept as an alias of violet)
    icon: placeholder icon name, or false for none
--}}
@props(['src' => null, 'alt' => '', 'icon' => 'image', 'label' => null, 'tone' => 'primary'])

@php
    [$surface, $blobA, $blobB, $iconColor, $mountain] = match ($tone) {
        'pink' => ['bg-accent-pink/10', 'bg-accent-pink/30', 'bg-accent-orchid/25', 'text-accent-pink-strong', 'Asset 16@3x.png'],
        'yellow' => ['bg-brand-yellow/30', 'bg-brand-yellow/70', 'bg-secondary/25', 'text-secondary-strong', 'Asset 16@3x.png'],
        'violet', 'cyan' => ['bg-accent-violet/10', 'bg-accent-violet/30', 'bg-accent-pink/20', 'text-accent-violet-strong', 'Asset 17@3x.png'],
        default => ['bg-primary/8', 'bg-accent-violet/30', 'bg-secondary/25', 'text-primary', 'Asset 17@3x.png'],
    };
@endphp

<div {{ $attributes->class(['relative isolate overflow-hidden rounded-2xl', $src ? 'bg-border' : $surface]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" class="absolute inset-0 -z-10 size-full object-cover" loading="lazy" decoding="async">
    @else
        <div class="absolute -left-[12%] -top-[20%] -z-10 aspect-square w-[55%] rounded-full blur-2xl {{ $blobA }}" aria-hidden="true"></div>
        <div class="absolute -bottom-[25%] -right-[10%] -z-10 aspect-square w-[60%] rounded-full blur-2xl {{ $blobB }}" aria-hidden="true"></div>
        <x-brand-image :file="$mountain" class="absolute -bottom-[2%] left-1/2 -z-10 w-[115%] -translate-x-1/2 opacity-30" />
    @endif

    <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 p-4 text-center" @if (! $src && $alt) role="img" aria-label="{{ $alt }}" @endif>
        @if (! $src && $icon)
            <span class="grid size-12 place-items-center rounded-2xl bg-surface/85 shadow-sm backdrop-blur {{ $iconColor }} md:size-14">
                <x-icon :name="$icon" class="size-6 md:size-7" />
            </span>
        @endif
        @if (! $src && $label)
            <span class="text-caption font-medium text-foreground/70">{{ $label }}</span>
        @endif
        {{ $slot }}
    </div>
</div>

{{--
    Name with a secondary line (email or registration ID) for table cells.

    avatar:     the User whose avatar to show, if any
    detailHref: turns the secondary line into a link (for example mailto:)
--}}
@props(['name', 'detail' => null, 'avatar' => null, 'detailHref' => null])

<div {{ $attributes->class('flex min-w-0 items-center gap-3') }}>
    @if ($avatar)
        <x-avatar :user="$avatar" class="size-9" />
    @endif
    <div class="min-w-0">
        <p class="max-w-52 truncate xl:max-w-64 font-medium text-foreground">{{ $name }}</p>
        @if ($detail && $detailHref)
            <a href="{{ $detailHref }}" class="block max-w-52 truncate xl:max-w-64 text-caption text-muted underline decoration-muted/40 underline-offset-2 hover:text-primary hover:decoration-primary">{{ $detail }}</a>
        @elseif ($detail)
            <p class="max-w-52 truncate xl:max-w-64 text-caption text-muted">{{ $detail }}</p>
        @endif
    </div>
</div>

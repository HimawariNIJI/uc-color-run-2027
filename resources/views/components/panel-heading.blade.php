{{--
    Centered page title, optional description and the divider used at the top of panels.
--}}
@props(['title', 'description' => null, 'level' => 'h1', 'divider' => true])

<div {{ $attributes->class(['flex flex-col items-center text-center', $divider ? 'border-b border-border pb-6 md:pb-8' : '']) }}>
    <{{ $level }} class="text-h2 text-primary">{{ $title }}</{{ $level }}>
    <x-brand-image file="4@3x.png" class="-mt-1 w-28 sm:w-36" />
    @if ($description)
        <p class="mx-auto mt-3 max-w-xl text-body text-muted">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>

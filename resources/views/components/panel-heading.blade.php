{{--
    Centered page title, optional description and the divider used at the top of panels.
--}}
@props(['title', 'description' => null, 'level' => 'h1', 'divider' => true])

<div {{ $attributes->class(['text-center', $divider ? 'border-b border-border pb-6 md:pb-8' : '']) }}>
    <{{ $level }} class="text-h2">{{ $title }}</{{ $level }}>
    @if ($description)
        <p class="mx-auto mt-3 max-w-xl text-body text-muted">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>

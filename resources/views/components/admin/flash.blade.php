@php
    $messages = collect(['success', 'warning', 'error', 'info'])
        ->filter(fn (string $tone): bool => session()->has($tone))
        ->mapWithKeys(fn (string $tone): array => [$tone => session($tone)]);
@endphp

@if ($messages->isNotEmpty())
    <div {{ $attributes->class('mb-6 flex flex-col gap-2') }}>
        @foreach ($messages as $tone => $message)
            <x-alert :tone="$tone" dismissible>{{ $message }}</x-alert>
        @endforeach
    </div>
@endif

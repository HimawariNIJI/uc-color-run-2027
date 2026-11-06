@php
    $messages = collect(['success', 'error', 'info'])
        ->filter(fn (string $tone): bool => session()->has($tone))
        ->mapWithKeys(fn (string $tone): array => [$tone => session($tone)]);
@endphp

@if ($messages->isNotEmpty())
    <div class="page-container flex flex-col gap-2 pt-6">
        @foreach ($messages as $tone => $message)
            <x-alert :tone="$tone" dismissible class="mx-auto w-full max-w-content">{{ $message }}</x-alert>
        @endforeach
    </div>
@endif

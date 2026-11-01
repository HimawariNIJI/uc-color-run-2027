{{--
    Label + control + hint + validation error. The control goes in the slot and should use
    the same id as "for".
--}}
@props(['label', 'for', 'required' => false, 'hint' => null, 'error' => null])

@php($errorKey = $error ?? $for)

<div {{ $attributes->class('flex flex-col gap-1.5') }}>
    <label for="{{ $for }}" class="text-label text-foreground">
        {{ $label }}@if ($required)<span class="text-error"> *</span>@endif
    </label>

    {{ $slot }}

    @if ($hint)
        <p id="{{ $for }}-hint" class="text-caption text-muted">{{ $hint }}</p>
    @endif

    @error($errorKey)
        <p id="{{ $for }}-error" class="flex items-start gap-1.5 text-caption font-medium text-error">
            <x-icon name="circle-alert" class="mt-px size-3.5" />
            {{ $message }}
        </p>
    @enderror
</div>

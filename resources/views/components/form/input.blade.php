@props(['name', 'value' => null, 'type' => 'text', 'pill' => false, 'icon' => null])

@php
    $id = $attributes->get('id', $name);
    // Array names such as members[0][full_name] are looked up as members.0.full_name.
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($key);
@endphp

<div class="relative">
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($key, $value) }}"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->class([
            'form-control h-12',
            $pill ? 'rounded-full pl-5' : 'rounded-xl pl-4',
            $icon ? 'pr-11' : ($pill ? 'pr-5' : 'pr-4'),
        ]) }}
    >
    @if ($icon)
        <x-icon :name="$icon" class="pointer-events-none absolute right-4 top-1/2 size-4.5 -translate-y-1/2 text-muted" />
    @endif
</div>

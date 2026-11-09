{{--
    options: [value => label]. The placeholder renders as a disabled empty option.
--}}
@props(['name', 'options' => [], 'selected' => null, 'placeholder' => null])

@php
    $id = $attributes->get('id', $name);
    // Array names such as members[0][gender] are looked up as members.0.gender.
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($key);
    $current = (string) old($key, $selected);
@endphp

<div class="relative">
    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->class('form-control h-12 cursor-pointer appearance-none truncate rounded-xl pl-4 pr-11') }}
    >
        @if ($placeholder)
            <option value="" disabled @selected($current === '')>{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected($current === (string) $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-icon name="chevron-down" class="pointer-events-none absolute right-4 top-1/2 size-4.5 -translate-y-1/2 text-muted" />
</div>

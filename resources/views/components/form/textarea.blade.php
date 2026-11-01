@props(['name', 'value' => null])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
@endphp

<textarea
    name="{{ $name }}"
    id="{{ $id }}"
    @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
    {{ $attributes->except('id')->class('form-control min-h-32 resize-y rounded-2xl px-5 py-3') }}
>{{ old($name, $value) }}</textarea>

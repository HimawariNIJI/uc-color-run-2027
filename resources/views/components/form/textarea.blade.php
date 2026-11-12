{{--
    pill: the rounder, wider look that sits next to pill inputs. Set it to false next to regular
    inputs (rounded-xl), as on the registration form.
--}}
@props(['name', 'value' => null, 'pill' => true])

@php
    $id = $attributes->get('id', $name);
    // Array names such as members[0][medical_history] are looked up as members.0.medical_history.
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($key);
@endphp

<textarea
    name="{{ $name }}"
    id="{{ $id }}"
    @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
    {{ $attributes->except('id')->class([
        'form-control resize-y',
        $pill ? 'min-h-32 rounded-2xl px-5 py-3' : 'min-h-28 rounded-xl px-4 py-3',
    ]) }}
>{{ old($key, $value) }}</textarea>

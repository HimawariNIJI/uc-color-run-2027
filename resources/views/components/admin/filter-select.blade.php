{{--
    Pill filter dropdown whose first option ("All …") clears the filter.
    It submits its form as soon as it changes (see the auto-submit script).

    options: [value => label]
--}}
@props(['name', 'options', 'selected' => null, 'allLabel', 'label'])

<div {{ $attributes->class('relative min-w-0') }}>
    <label for="{{ $name }}" class="sr-only">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}" class="form-control h-11 cursor-pointer appearance-none truncate rounded-full pl-5 pr-11">
        <option value="">{{ $allLabel }}</option>
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    <x-icon name="chevron-down" class="pointer-events-none absolute right-4 top-1/2 size-4.5 -translate-y-1/2 text-muted" />
</div>

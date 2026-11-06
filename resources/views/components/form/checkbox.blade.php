{{--
    Round checkbox, as drawn in the design. The label text goes in the slot.
--}}
@props(['name', 'checked' => false])

@php($hasError = $errors->has($name))

<div {{ $attributes->class('flex flex-col gap-1.5') }}>
    <label class="flex cursor-pointer items-start gap-3">
        <span class="relative mt-px grid size-5 shrink-0 place-items-center">
            <input
                type="checkbox"
                name="{{ $name }}"
                id="{{ $name }}"
                value="1"
                @checked(old($name, $checked))
                @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
                class="peer size-5 cursor-pointer appearance-none rounded-full border-2 bg-surface transition-colors checked:border-primary checked:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary {{ $hasError ? 'border-error' : 'border-foreground/30 hover:border-primary' }}"
            >
            <x-icon name="check" class="pointer-events-none absolute size-3 text-white opacity-0 peer-checked:opacity-100" stroke-width="3.5" />
        </span>
        <span class="text-body-sm text-foreground">{{ $slot }}</span>
    </label>

    @error($name)
        <p id="{{ $name }}-error" class="flex items-start gap-1.5 pl-8 text-caption font-medium text-error">
            <x-icon name="circle-alert" class="mt-px size-3.5" />
            {{ $message }}
        </p>
    @enderror
</div>

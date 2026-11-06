{{-- Pill search field; Enter or the search icon submits the surrounding filter form. --}}
@props(['name' => 'search', 'value' => null, 'placeholder', 'label'])

<div {{ $attributes->class('relative min-w-0') }}>
    <label for="{{ $name }}" class="sr-only">{{ $label }}</label>
    <input
        type="search"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        maxlength="100"
        autocomplete="off"
        enterkeyhint="search"
        class="form-control h-11 rounded-full pl-5 pr-12 [&::-webkit-search-cancel-button]:hidden"
    >
    <button type="submit" class="absolute right-1.5 top-1/2 grid size-8 -translate-y-1/2 place-items-center rounded-full text-muted transition-colors hover:bg-primary/10 hover:text-primary focus-visible:outline-2 focus-visible:outline-primary" aria-label="Cari">
        <x-icon name="search" class="size-4.5" />
    </button>
</div>

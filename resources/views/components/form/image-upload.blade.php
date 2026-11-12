{{--
    Photo picker with a preview of the chosen image (resources/js/modules/image-upload.js). The
    real file input stays in the page for keyboards and screen readers; the visible boxes are its
    labels, so clicking either one opens the picker again to choose another photo.

    saved: a photo is already stored. Choosing another one replaces it when the form is saved.
    Wrap it in <x-form.field> with the same id, which shows the server's validation error.
--}}
@props([
    'name',
    'maxKilobytes',
    'mimeTypes' => ['image/jpeg', 'image/png'],
    'saved' => false,
    'label' => 'foto',
    'typesLabel' => 'JPG, JPEG, atau PNG',
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $maxMegabytes = rtrim(rtrim(number_format($maxKilobytes / 1024, 1, ',', ''), '0'), ',');
    // Browsers never keep a chosen file when the form comes back with errors.
    $mustChooseAgain = ! $saved && ! $hasError && $errors->any();
    $boxFocus = 'peer-focus-visible:border-primary peer-focus-visible:ring-4 peer-focus-visible:ring-brand-yellow/70';
@endphp

<div
    class="flex flex-col gap-2"
    data-image-upload
    data-max-bytes="{{ $maxKilobytes * 1024 }}"
    data-types="{{ implode(',', $mimeTypes) }}"
    data-type-error="{{ ucfirst($label) }} harus berupa gambar {{ $typesLabel }}."
    data-size-error="Ukuran {{ $label }} maksimal {{ $maxMegabytes }} MB."
>
    <input
        type="file"
        name="{{ $name }}"
        id="{{ $id }}"
        accept="{{ implode(',', $mimeTypes) }}"
        class="peer sr-only"
        data-image-upload-input
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id') }}
    >

    <label
        for="{{ $id }}"
        data-image-upload-empty
        @class([
            'flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border bg-background px-4 py-3 transition-colors hover:border-primary/40',
            'border-dashed' => ! $saved,
            'border-border' => ! $hasError,
            'border-error' => $hasError,
            $boxFocus,
        ])
    >
        @if ($saved)
            <x-icon name="circle-check" class="size-5 shrink-0 text-success-strong" />
            <span class="min-w-0 flex-1">
                <span class="block text-body-sm font-semibold">{{ ucfirst($label) }} sudah tersimpan</span>
                <span class="block text-caption text-muted">Pilih foto baru jika ingin menggantinya.</span>
            </span>
            <span class="shrink-0 text-body-sm font-semibold text-primary">Ganti foto</span>
        @else
            <x-icon name="image" class="size-5 shrink-0 text-primary" />
            <span class="min-w-0 flex-1">
                <span class="block text-body-sm font-semibold text-primary">Pilih {{ $label }}</span>
                <span class="block text-caption text-muted">{{ $typesLabel }}, maks. {{ $maxMegabytes }} MB</span>
            </span>
        @endif
    </label>

    <div data-image-upload-selected hidden class="overflow-hidden rounded-xl border border-border bg-background {{ $boxFocus }}">
        <img src="data:," alt="Pratinjau {{ $label }} yang dipilih" class="aspect-3/2 w-full bg-foreground/5 object-contain" data-image-upload-preview>
        <div class="flex items-center gap-3 border-t border-border bg-surface py-2 pl-4 pr-2">
            <x-icon name="image" class="size-4 shrink-0 text-muted" />
            <p class="min-w-0 flex-1 truncate text-caption text-muted" data-image-upload-name></p>
            <label for="{{ $id }}" class="shrink-0 cursor-pointer rounded-lg px-2.5 py-1.5 text-body-sm font-semibold text-primary transition-colors hover:bg-primary/10">
                Ganti foto
            </label>
        </div>
    </div>

    <p id="{{ $id }}-client-error" hidden class="flex items-start gap-1.5 text-caption font-medium text-error" data-image-upload-error>
        <x-icon name="circle-alert" class="mt-px size-3.5" />
        <span data-image-upload-error-text></span>
    </p>

    @if ($mustChooseAgain)
        <p class="flex items-start gap-1.5 text-caption text-muted" data-image-upload-reminder>
            <x-icon name="info" class="mt-px size-3.5 shrink-0" />
            Pilih ulang {{ $label }}: foto yang tadi dipilih tidak ikut tersimpan karena formulir belum terkirim.
        </p>
    @endif
</div>

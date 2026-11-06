{{--
    Progress indicator for the four registration steps.
--}}
@props(['current'])

@php($steps = ['Tipe Registrasi', 'Tipe Harga', 'Jarak Lari', 'Data Peserta'])

<ol {{ $attributes->class('flex items-center justify-center gap-2 sm:gap-3') }} aria-label="Langkah registrasi">
    @foreach ($steps as $index => $label)
        @php($number = $index + 1)
        <li class="flex items-center gap-2 sm:gap-3" @if ($number === $current) aria-current="step" @endif>
            <span class="flex items-center gap-2">
                <span @class([
                    'grid size-6 place-items-center rounded-full text-caption font-bold',
                    'bg-primary text-white' => $number < $current,
                    'bg-primary text-white ring-4 ring-primary/15' => $number === $current,
                    'bg-foreground/6 text-muted' => $number > $current,
                ])>
                    @if ($number < $current)
                        <x-icon name="check" class="size-3.5" stroke-width="3" />
                        <span class="sr-only">Selesai:</span>
                    @else
                        {{ $number }}
                    @endif
                </span>
                <span @class([
                    'text-caption font-medium',
                    'text-foreground' => $number === $current,
                    'hidden text-muted sm:inline' => $number !== $current,
                ])>{{ $label }}</span>
            </span>
            @unless ($loop->last)
                <span class="h-px w-5 bg-border sm:w-10" aria-hidden="true"></span>
            @endunless
        </li>
    @endforeach
</ol>

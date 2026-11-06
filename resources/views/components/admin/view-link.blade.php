{{-- "View →" action that opens details in the admin modal. --}}
@props(['href', 'subject'])

<a href="{{ $href }}" {{ $attributes->class('inline-flex items-center gap-1 whitespace-nowrap rounded-md font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary') }} data-admin-modal-link>
    View<span class="sr-only"> detail {{ $subject }}</span>
    <x-icon name="arrow-right" class="size-4" />
</a>

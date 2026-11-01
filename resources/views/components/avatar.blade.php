@props(['user'])

<span {{ $attributes->class('grid shrink-0 place-items-center overflow-hidden rounded-full bg-primary/10 text-primary') }}>
    @if ($user->avatar)
        <img src="{{ $user->avatar }}" alt="" class="size-full object-cover" referrerpolicy="no-referrer" loading="lazy">
    @else
        <x-icon name="user" class="size-[55%]" />
    @endif
</span>

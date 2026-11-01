@props(['colspan', 'title', 'icon' => 'inbox'])

<tr {{ $attributes }}>
    <td colspan="{{ $colspan }}" class="py-12!">
        <div class="flex flex-col items-center gap-2 text-center">
            <span class="grid size-11 place-items-center rounded-2xl bg-foreground/5 text-muted">
                <x-icon :name="$icon" class="size-5" />
            </span>
            <p class="font-semibold text-foreground">{{ $title }}</p>
            @if ($slot->isNotEmpty())
                <p class="max-w-sm text-caption text-muted">{{ $slot }}</p>
            @endif
        </div>
    </td>
</tr>

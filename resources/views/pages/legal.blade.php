{{-- Shared template for Race Regulation and Privacy Policy. --}}
<x-layouts.app :title="$title" :description="$description">
    <div class="page-container page-section">
        <x-panel>
            <x-panel-heading :title="$title" :description="$description" />

            <ol class="mt-6 flex flex-col gap-5 md:mt-8">
                @foreach ($items as $item)
                    <li class="flex gap-4">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-primary/10 text-caption font-bold text-primary">{{ $loop->iteration }}</span>
                        <p class="pt-0.5 text-body text-foreground/85">{{ $item }}</p>
                    </li>
                @endforeach
            </ol>

            <div class="mt-8 flex flex-col items-center justify-between gap-3 border-t border-border pt-6 text-body-sm text-muted sm:flex-row">
                <p>
                    Ada pertanyaan?
                    <a href="{{ route('contact') }}" class="font-semibold text-primary hover:underline">Hubungi panitia</a>
                </p>
                <p class="flex gap-4">
                    <a href="{{ route('race.regulation') }}" class="font-medium hover:text-primary {{ request()->routeIs('race.regulation') ? 'text-primary' : '' }}">Race Regulation</a>
                    <a href="{{ route('privacy') }}" class="font-medium hover:text-primary {{ request()->routeIs('privacy') ? 'text-primary' : '' }}">Privacy Policy</a>
                </p>
            </div>
        </x-panel>
    </div>
</x-layouts.app>

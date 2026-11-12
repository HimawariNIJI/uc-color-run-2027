{{-- One accepted scan in Recent QR Scanned: a bundle is one row with every participant's BIB. --}}
@php
    $registration = $scan->registration;
    $bibNumbers = $registration->participantBibNumbers();
@endphp

<tr data-scanner-row>
    <td class="whitespace-nowrap">
        <p class="font-medium tabular-nums">{{ $registration->code }}</p>
        <p class="text-caption text-muted">{{ $scan->scanned_at->translatedFormat('j M, H:i') }}</p>
    </td>
    <td>
        <x-admin.person
            :name="$registration->full_name"
            :detail="count($bibNumbers) > 1 ? count($bibNumbers).' peserta: '.$registration->members->pluck('full_name')->prepend($registration->full_name)->implode(', ') : $registration->email"
        />
    </td>
    <td>
        <x-admin.category-badge :category="$registration->category" />
        <p class="mt-1 text-caption text-muted">{{ $registration->type === \App\Enums\RegistrationType::Bundling ? $registration->packageName() : $registration->raceLabel() }}</p>
    </td>
    <td class="font-semibold tabular-nums">
        @foreach ($bibNumbers as $bibNumber)
            <span class="block">{{ $bibNumber }}</span>
        @endforeach
    </td>
    <td><x-admin.view-link :href="route('admin.scanner.show', $scan)" :subject="$registration->code" /></td>
</tr>

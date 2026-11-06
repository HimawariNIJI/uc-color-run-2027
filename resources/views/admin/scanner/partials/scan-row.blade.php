<tr data-scanner-row>
    <td class="whitespace-nowrap">
        <p class="font-medium tabular-nums">{{ $registration->code }}</p>
        <p class="text-caption text-muted">{{ $registration->bib_collected_at?->translatedFormat('j M, H:i') }}</p>
    </td>
    <td><x-admin.person :name="$registration->full_name" :detail="$registration->email" :detail-href="'mailto:'.$registration->email" /></td>
    <td><x-admin.category-badge :category="$registration->category" /></td>
    <td class="font-semibold tabular-nums">{{ $registration->bib_number }}</td>
</tr>

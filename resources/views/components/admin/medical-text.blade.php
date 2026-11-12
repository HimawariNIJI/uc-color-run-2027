{{--
    Riwayat Penyakit or Alergi Obat as the participant typed it, keeping their line breaks.
    Registrations made before these fields existed have none.
--}}
@props(['value'])

@if (filled($value))
    <span class="whitespace-pre-line">{{ $value }}</span>
@else
    <span class="font-normal text-muted">Belum diisi</span>
@endif

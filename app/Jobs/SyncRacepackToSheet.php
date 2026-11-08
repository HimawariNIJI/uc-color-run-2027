<?php

namespace App\Jobs;

use App\Models\Participant;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Menyalin status "Sudah Diambil" satu peserta ke Google Sheets.
 * Dijalankan lewat queue supaya scanner tidak menunggu Google API,
 * dan otomatis dicoba ulang kalau Google sedang lambat / kena rate limit.
 */
class SyncRacepackToSheet implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public int $participantId)
    {
    }

    /** Jeda (detik) antar percobaan ulang. */
    public function backoff(): array
    {
        return [5, 15, 60, 180];
    }

    public function handle(GoogleSheetsService $sheets): void
    {
        $participant = Participant::find($this->participantId);

        if (! $participant || ! $participant->racepack_collected || $participant->sheets_synced_at) {
            return;
        }

        $found = $sheets->updateRacepackByBib(
            $participant->bib_number,
            'Sudah Diambil',
            $participant->collectedAtLocal()
        );

        if (! $found) {
            // Bukan error sementara: baris belum ada di sheet. Dibiarkan
            // tanpa sheets_synced_at supaya `php artisan racepack:sync-sheets`
            // bisa mencoba lagi nanti.
            Log::warning('BIB tidak ditemukan di Google Sheets', [
                'bib_number' => $participant->bib_number,
            ]);

            return;
        }

        $participant->forceFill(['sheets_synced_at' => now()])->save();
    }
}

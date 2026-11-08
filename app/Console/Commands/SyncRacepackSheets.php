<?php

namespace App\Console\Commands;

use App\Jobs\SyncRacepackToSheet;
use App\Models\Participant;
use Illuminate\Console\Command;

class SyncRacepackSheets extends Command
{
    protected $signature = 'racepack:sync-sheets {--now : Jalankan langsung tanpa queue}';

    protected $description = 'Kirim ulang peserta yang racepack-nya sudah diambil tapi belum tersinkron ke Google Sheets';

    public function handle(): int
    {
        $pending = Participant::query()
            ->where('racepack_collected', true)
            ->whereNull('sheets_synced_at')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Semua data sudah tersinkron.');

            return self::SUCCESS;
        }

        foreach ($pending as $participant) {
            $this->option('now')
                ? SyncRacepackToSheet::dispatchSync($participant->id)
                : SyncRacepackToSheet::dispatch($participant->id);
        }

        $this->info($pending->count().' peserta '.($this->option('now') ? 'diproses.' : 'dimasukkan ke queue.'));

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Exceptions\GoogleSheetsException;
use App\Jobs\SyncRegistrationToParticipantsSheet;
use App\Models\Registration;
use App\Support\GoogleSheets;
use App\Support\ParticipantsSheet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('sheets:sync
    {codes?* : Registration codes to write; every registration when left out}
    {--queue : Queue one sync job per registration instead of writing now}')]
#[Description('Write registrations from the database to the Participants sheet (recovers failed or missing rows, never duplicates)')]
class SyncParticipantsSheet extends Command
{
    /**
     * Registrations written per batch: a handful of Google requests per batch, however large.
     */
    private const BATCH = 200;

    public function handle(): int
    {
        if ($problem = GoogleSheets::configurationProblem()) {
            $this->components->error("Google Sheets is not configured correctly: {$problem}");

            return self::FAILURE;
        }

        $codes = $this->argument('codes');
        $query = Registration::query()->when($codes !== [], fn ($query) => $query->whereIn('code', $codes))->orderBy('id');

        if ($codes !== [] && ($missing = array_diff($codes, (clone $query)->pluck('code')->all())) !== []) {
            $this->components->warn('No registration with code: '.implode(', ', $missing));
        }

        if ($this->option('queue')) {
            $count = 0;

            foreach ($query->pluck('id') as $id) {
                SyncRegistrationToParticipantsSheet::dispatch($id);
                $count++;
            }

            $this->components->info("Queued {$count} registration(s). A queue worker writes them to the sheet.");

            return self::SUCCESS;
        }

        $sheet = new ParticipantsSheet(GoogleSheets::fromConfig());
        $totals = ['registrations' => 0, 'updated' => 0, 'added' => 0];

        try {
            $query->with(ParticipantsSheet::RELATIONS)->chunkById(self::BATCH, function (Collection $registrations) use ($sheet, &$totals): void {
                $result = $sheet->sync($registrations);
                $totals['registrations'] += $registrations->count();
                $totals['updated'] += $result['updated'];
                $totals['added'] += $result['added'];
            });
        } catch (GoogleSheetsException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Wrote {$totals['registrations']} registration(s): {$totals['updated']} row(s) updated, {$totals['added']} row(s) added.");

        return self::SUCCESS;
    }
}

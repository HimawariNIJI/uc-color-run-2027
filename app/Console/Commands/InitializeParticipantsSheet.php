<?php

namespace App\Console\Commands;

use App\Exceptions\GoogleSheetsException;
use App\Support\GoogleSheets;
use App\Support\ParticipantsSheet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('sheets:initialize {--check : Only check the configuration and access to the spreadsheet; change nothing}')]
#[Description('Prepare the Participants tab in Google Sheets (safe to run again: existing rows are kept)')]
class InitializeParticipantsSheet extends Command
{
    public function handle(): int
    {
        if ($problem = GoogleSheets::configurationProblem()) {
            $this->components->error("Google Sheets is not configured correctly: {$problem}");

            return self::FAILURE;
        }

        $sheet = new ParticipantsSheet(GoogleSheets::fromConfig());

        try {
            if ($this->option('check')) {
                $status = $sheet->status();

                $this->components->info('The service account signed in and can open the spreadsheet.');
                $this->components->twoColumnDetail('Participants tab', $status['tab'] ? 'present' : 'missing');
                $this->components->twoColumnDetail('Header row', $status['header'] ? 'in place' : 'missing or different');
                $this->components->twoColumnDetail('Participant rows', (string) $status['participantRows']);

                return self::SUCCESS;
            }

            foreach ($sheet->initialize() as $step) {
                $this->components->info($step);
            }
        } catch (GoogleSheetsException $exception) {
            $this->components->error(match ($exception->status) {
                403 => 'The service account may not open this spreadsheet. Share it with the client_email from the key file as Editor.',
                404 => 'The spreadsheet was not found. Check GOOGLE_SHEETS_SPREADSHEET_ID.',
                default => $exception->getMessage(),
            });

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

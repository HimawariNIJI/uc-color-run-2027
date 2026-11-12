<?php

namespace App\Jobs;

use App\Exceptions\GoogleSheetsException;
use App\Models\Registration;
use App\Support\GoogleSheets;
use App\Support\ParticipantsSheet;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Write one registration's participants to the Participants sheet.
 *
 * The job reads the registration when it runs, so it always writes the latest committed state: a
 * delayed or repeated job can never put older data back. One job at a time touches the sheet.
 * Temporary Google problems (rate limits, server errors, connection loss) are retried with growing
 * pauses for up to a day; configuration and permission problems fail at once and are logged, to be
 * fixed and then recovered with "php artisan sheets:sync".
 */
class SyncRegistrationToParticipantsSheet implements ShouldQueue
{
    use Queueable;

    /**
     * Unexpected errors allowed before giving up. Waiting for another sync to finish does not count.
     */
    public int $maxExceptions = 5;

    public function __construct(public readonly int $registrationId) {}

    /**
     * Queue one sync of the registration once the current database transaction has committed.
     * Changes made close together (the saves of one registration or payment) share one sync. Never
     * throws: the registration, payment or scan that triggered it must succeed either way.
     */
    public static function queueFor(int $registrationId): void
    {
        if (! GoogleSheets::isConfigured()) {
            return;
        }

        DB::afterCommit(function () use ($registrationId): void {
            try {
                if (Cache::add(self::queuedKey($registrationId), true, now()->addMinutes(2))) {
                    self::dispatch($registrationId)->delay(now()->addSeconds(5));
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('participants-sheet'))->releaseAfter(5)->expireAfter(180)];
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    public function handle(): void
    {
        Cache::forget(self::queuedKey($this->registrationId));

        $registration = Registration::query()->with(ParticipantsSheet::RELATIONS)->find($this->registrationId);

        if ($registration === null) {
            return;
        }

        try {
            (new ParticipantsSheet(GoogleSheets::fromConfig()))->sync([$registration]);
        } catch (GoogleSheetsException $exception) {
            if (! $exception->isTemporary()) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }

    /**
     * Only the registration's database ID and Google's short error are logged, never participant
     * data or credentials.
     */
    public function failed(?Throwable $exception): void
    {
        Log::warning("Google Sheets: registration #{$this->registrationId} could not be written to the Participants sheet. Fix the problem, then run \"php artisan sheets:sync\".", [
            'error' => $exception?->getMessage(),
        ]);
    }

    private static function queuedKey(int $registrationId): string
    {
        return "participants-sheet:queued:{$registrationId}";
    }
}

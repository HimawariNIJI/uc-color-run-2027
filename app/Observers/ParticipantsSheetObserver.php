<?php

namespace App\Observers;

use App\Enums\QrScanStatus;
use App\Jobs\SyncRegistrationToParticipantsSheet;
use App\Models\QrScan;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Model;

/**
 * Keeps the Participants sheet in step with the database. Every saved change to a registration,
 * its bundle members, its payment attempts or an accepted racepack scan queues a sync of that
 * registration after the transaction commits: a new registration, a payment verified with Midtrans
 * (settlement, expiry, cancellation or failure), BIBs being issued and a successful scan. Refused
 * scans change nothing in the sheet. Does nothing while Google Sheets is not configured.
 */
class ParticipantsSheetObserver
{
    public function saved(Model $model): void
    {
        $this->queueSync($model);
    }

    public function deleted(Model $model): void
    {
        $this->queueSync($model);
    }

    private function queueSync(Model $model): void
    {
        if ($model instanceof QrScan && $model->status !== QrScanStatus::Accepted) {
            return;
        }

        $registrationId = $model instanceof Registration ? $model->getKey() : $model->getAttribute('registration_id');

        if ($registrationId !== null) {
            SyncRegistrationToParticipantsSheet::queueFor((int) $registrationId);
        }
    }
}

<?php

namespace App\Models;

use App\Enums\QrScanRejection;
use App\Enums\QrScanSource;
use App\Enums\QrScanStatus;
use App\Observers\ParticipantsSheetObserver;
use App\Support\RacepackQr;
use Database\Factories\QrScanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * One scan at the racepack desk (see Admin\QrScannerController). An accepted scan links to the
 * registration, so a bundle's single scan covers every participant through its members; the
 * payload keeps the participant IDs that were scanned. Rejected scans are an audit trail only and
 * never appear in Recent QR Scanned. No participant details are stored here.
 */
#[ObservedBy([ParticipantsSheetObserver::class])]
#[Fillable(['registration_id', 'admin_id', 'status', 'rejection_reason', 'source', 'payload', 'bib_handed_over', 'scanned_at'])]
class QrScan extends Model
{
    /** @use HasFactory<QrScanFactory> */
    use HasFactory;

    /**
     * The same scan repeated by one admin within this many seconds (camera frames, double submits)
     * is stored once.
     */
    public const DUPLICATE_WINDOW_SECONDS = 10;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QrScanStatus::class,
            'rejection_reason' => QrScanRejection::class,
            'source' => QrScanSource::class,
            'bib_handed_over' => 'boolean',
            'scanned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    #[Scope]
    protected function accepted(Builder $query): void
    {
        $query->where('status', QrScanStatus::Accepted);
    }

    /**
     * Record an accepted scan and hand over the BIB when it was not collected yet.
     *
     * The same registration scanned again by the same admin within the duplicate window returns that
     * earlier scan instead (its wasRecentlyCreated is false). The registration row is locked first,
     * so two requests from repeated camera frames cannot both create a scan.
     */
    public static function recordAccepted(Registration $registration, Admin $admin, QrScanSource $source, string $payload): self
    {
        return DB::transaction(function () use ($registration, $admin, $source, $payload): self {
            Registration::query()->whereKey($registration->getKey())->lockForUpdate()->first();

            $repeated = static::query()
                ->accepted()
                ->where('registration_id', $registration->getKey())
                ->where('admin_id', $admin->getKey())
                ->where('scanned_at', '>=', now()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))
                ->latest('scanned_at')
                ->first();

            if ($repeated !== null) {
                return $repeated;
            }

            return static::query()->create([
                'registration_id' => $registration->getKey(),
                'admin_id' => $admin->getKey(),
                'status' => QrScanStatus::Accepted,
                'source' => $source,
                'payload' => $payload,
                'bib_handed_over' => $registration->markBibAsCollected($admin),
                'scanned_at' => now(),
            ]);
        });
    }

    /**
     * Keep a refused scan for the audit trail, once per admin, reason and payload within the
     * duplicate window. Only payloads in the racepack ID format are kept: anything else a camera
     * picks up could be someone's personal data.
     */
    public static function recordRejection(Admin $admin, QrScanSource $source, QrScanRejection $reason, string $payload, ?Registration $registration = null): void
    {
        $payload = RacepackQr::isSafeToStore($payload) ? $payload : null;

        $repeated = static::query()
            ->where('status', QrScanStatus::Rejected)
            ->where('admin_id', $admin->getKey())
            ->where('rejection_reason', $reason)
            ->where('payload', $payload)
            ->where('scanned_at', '>=', now()->subSeconds(self::DUPLICATE_WINDOW_SECONDS))
            ->exists();

        if ($repeated) {
            return;
        }

        static::query()->create([
            'registration_id' => $registration?->getKey(),
            'admin_id' => $admin->getKey(),
            'status' => QrScanStatus::Rejected,
            'rejection_reason' => $reason,
            'source' => $source,
            'payload' => $payload,
            'scanned_at' => now(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QrScanSource;
use App\Enums\QrScanStatus;
use App\Exceptions\RacepackQrRejected;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScanRacepackRequest;
use App\Models\Admin;
use App\Models\QrScan;
use App\Support\RacepackQr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Racepack collection desk. A participant's racepack QR (or an ID typed when no camera is
 * available) is checked here on the server against the database: only a paid registration whose
 * participants match the QR exactly is accepted. An accepted scan shows the registration's details,
 * hands over the BIB the first time and is recorded in Recent QR Scanned. Refused scans are
 * recorded for the audit trail only.
 */
class QrScannerController extends Controller
{
    private const RECENT_SCANS = 10;

    public function index(): View
    {
        return view('admin.scanner.index', [
            'recentScans' => QrScan::query()
                ->accepted()
                ->with(['registration.members', 'registration.ticketPrice'])
                ->latest('scanned_at')
                ->latest('id')
                ->limit(self::RECENT_SCANS)
                ->get(),
            'recentScanLimit' => self::RECENT_SCANS,
        ]);
    }

    /**
     * An accepted scan with the registration's details. Requests from the admin modal receive only
     * the modal content.
     */
    public function show(Request $request, QrScan $scan): View|string
    {
        abort_unless($scan->status === QrScanStatus::Accepted && $scan->registration_id !== null, 404);

        return view('admin.scanner.show', [
            'scan' => $this->withDetails($scan),
        ])->fragmentIf($request->hasHeader('X-Admin-Modal'), 'details');
    }

    /**
     * Check a scanned QR payload or a typed ID. Every check is a successful request; the outcome is
     * its tone (success, warning or error) so the scanner can show it to the desk staff.
     */
    public function store(ScanRacepackRequest $request): JsonResponse|RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $source = $request->filled('payload') ? QrScanSource::Camera : QrScanSource::Manual;
        $input = $source === QrScanSource::Camera ? trim($request->string('payload')->toString()) : $request->string('code')->toString();

        try {
            $registration = $source === QrScanSource::Camera ? RacepackQr::resolve($input) : RacepackQr::resolveTypedId($input);
        } catch (RacepackQrRejected $rejected) {
            QrScan::recordRejection($admin, $source, $rejected->reason, $input, $rejected->registration);

            return $this->respond($request, 'error', $rejected->getMessage());
        }

        $scan = $this->withDetails(QrScan::recordAccepted($registration, $admin, $source, $input));
        $registration = $scan->registration;
        $bibNumbers = implode(', ', $registration->participantBibNumbers());
        $participants = count($registration->participantBibNumbers());

        [$tone, $message] = match (true) {
            ! $scan->wasRecentlyCreated => ['warning', "QR {$registration->code} baru saja dipindai, jadi tidak dicatat dua kali."],
            ! $scan->bib_handed_over => ['warning', "{$registration->code} sudah mengambil BIB {$bibNumbers} pada {$registration->bib_collected_at->translatedFormat('j M Y, H:i')}."],
            $participants > 1 => ['success', "Successfully scanned {$registration->code}. BIB Numbers: {$bibNumbers} ({$participants} peserta)."],
            default => ['success', "Successfully scanned {$registration->code}. Your BIB Number is {$bibNumbers}."],
        };

        return $this->respond($request, $tone, $message, $scan);
    }

    private function withDetails(QrScan $scan): QrScan
    {
        return $scan->load([
            'admin:id,name',
            'registration.members',
            'registration.ticketPrice',
            'registration.bibCollector:id,name',
        ]);
    }

    /**
     * JSON for the scanner script: the message, the scan's details for the modal and, for a newly
     * recorded scan, its Recent QR Scanned row. Without JavaScript, the plain form is sent to the
     * scan's details page, or back to the scanner with the error.
     *
     * @param  'success'|'warning'|'error'  $tone
     */
    private function respond(ScanRacepackRequest $request, string $tone, string $message, ?QrScan $scan = null): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return $scan === null
                ? to_route('admin.scanner.index')->with($tone, $message)
                : to_route('admin.scanner.show', $scan)->with($tone, $message);
        }

        return response()->json([
            'tone' => $tone,
            'message' => $message,
            'modal' => $scan === null ? null : view('admin.scanner.show', ['scan' => $scan])->fragment('details'),
            'row' => $scan?->wasRecentlyCreated ? view('admin.scanner.partials.scan-row', ['scan' => $scan])->render() : null,
        ]);
    }
}

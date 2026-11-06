<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScanRacepackRequest;
use App\Models\Admin;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Racepack collection desk: scanning a participant's QR code hands over their BIB.
 */
class QrScannerController extends Controller
{
    private const RECENT_SCANS = 10;

    public function index(): View
    {
        return view('admin.scanner.index', [
            'recentScans' => Registration::query()
                ->bibCollected()
                ->latest('bib_collected_at')
                ->limit(self::RECENT_SCANS)
                ->get(),
            'recentScanLimit' => self::RECENT_SCANS,
        ]);
    }

    /**
     * Check a scanned QR code (or a typed registration ID) and record the BIB collection.
     *
     * Every checked code is a successful request; the outcome is reported as its tone
     * (success, warning or error) so the scanner can show it to the desk staff.
     */
    public function store(ScanRacepackRequest $request): JsonResponse|RedirectResponse
    {
        $scanned = $request->filled('payload')
            ? Registration::parseRacepackPayload($request->string('payload')->toString())
            : ['code' => $request->string('code')->toString(), 'bib_number' => null];

        if ($scanned === null) {
            return $this->respond($request, 'error', 'QR code tidak dikenali. Pastikan peserta menunjukkan QR racepack dari halaman My BIB.');
        }

        $registration = Registration::query()->firstWhere('code', $scanned['code']);

        if ($registration === null) {
            return $this->respond($request, 'error', "Registrasi {$scanned['code']} tidak ditemukan.");
        }

        if ($scanned['bib_number'] !== null && $scanned['bib_number'] !== $registration->bib_number) {
            return $this->respond($request, 'error', "QR code tidak cocok dengan data registrasi {$registration->code}.");
        }

        if (! $registration->isPaid()) {
            return $this->respond($request, 'error', "Registrasi {$registration->code} belum lunas, sehingga BIB belum dapat diberikan.");
        }

        /** @var Admin $admin */
        $admin = $request->user('admin');

        if (! $registration->markBibAsCollected($admin)) {
            $collectedAt = $registration->bib_collected_at->translatedFormat('j M Y, H:i');

            return $this->respond($request, 'warning', "{$registration->code} sudah mengambil BIB {$registration->bib_number} pada {$collectedAt}.");
        }

        return $this->respond(
            $request,
            'success',
            "Successfully scanned {$registration->code}. Your BIB Number is {$registration->bib_number}.",
            $registration,
        );
    }

    /**
     * JSON for the camera scanner, a redirect with a flash message for the plain form.
     *
     * @param  'success'|'warning'|'error'  $tone
     */
    private function respond(ScanRacepackRequest $request, string $tone, string $message, ?Registration $collected = null): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return to_route('admin.scanner.index')->with($tone, $message);
        }

        return response()->json([
            'tone' => $tone,
            'message' => $message,
            'row' => $collected ? view('admin.scanner.partials.scan-row', ['registration' => $collected])->render() : null,
        ]);
    }
}

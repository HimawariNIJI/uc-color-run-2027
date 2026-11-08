<?php

namespace App\Http\Controllers;

use App\Jobs\SyncRacepackToSheet;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RacepackController extends Controller
{
    /** Halaman scanner. */
    public function index()
    {
        // Hanya di lokal: tampilkan QR contoh supaya bisa dites tanpa kartu peserta.
        $testBib = app()->environment('local')
            ? (Participant::where('racepack_collected', false)->value('bib_number')
                ?? Participant::value('bib_number'))
            : null;

        return view('uc-color-run.racepack-scanner', [
            'recentScans' => Participant::recentlyCollected(),
            'testBib' => $testBib,
        ]);
    }

    /** Daftar scan terbaru (JSON). */
    public function recent(): JsonResponse
    {
        return response()->json([
            'data' => Participant::recentlyCollected(),
        ]);
    }

    /** Proses hasil scan QR: tandai racepack diambil + antrikan sinkron ke Google Sheets. */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bib_number' => ['required', 'string', 'max:32'],
        ]);

        $bib = trim($validated['bib_number']);

        // Kunci baris supaya dua scan bersamaan pada QR yang sama
        // tidak sama-sama dianggap "belum diambil".
        [$participant, $outcome] = DB::transaction(function () use ($bib) {
            $participant = Participant::where('bib_number', $bib)
                ->lockForUpdate()
                ->first();

            if (! $participant) {
                return [null, 'not_found'];
            }

            if ($participant->racepack_collected) {
                return [$participant, 'already_collected'];
            }

            $participant->update([
                'racepack_collected' => true,
                'collected_at' => now(),
                'sheets_synced_at' => null,
            ]);

            return [$participant, 'success'];
        });

        if ($outcome === 'not_found') {
            return response()->json([
                'success' => false,
                'status' => 'not_found',
                'message' => 'BIB Number not found.',
            ], 404);
        }

        if ($outcome === 'already_collected') {
            return response()->json([
                'success' => false,
                'status' => 'already_collected',
                'message' => "Racepack for {$participant->name} was already collected at {$participant->collectedAtLocal()}.",
                'data' => $participant->toScanArray(),
            ], 409);
        }

        // Sinkron ke Google Sheets di background (queue), tidak menahan scanner.
        SyncRacepackToSheet::dispatch($participant->id);

        $label = $participant->registration_id ?: $participant->name;

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => "Successfully scanned {$label}. Your BIB Number is {$participant->bib_number}.",
            'data' => $participant->toScanArray(),
            'recent' => Participant::recentlyCollected(),
        ]);
    }
}

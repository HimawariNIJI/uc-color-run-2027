<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use RuntimeException;

/**
 * Menulis status pengambilan racepack ke Google Sheets.
 *
 * Kolom sheet: A QR Token | B Nama | C Email | D BIB Number | E Kategori
 *              | F Status Racepack | G Waktu Pengambilan
 */
class GoogleSheetsService
{
    private ?Sheets $service = null;

    /**
     * @return bool true jika baris dengan BIB tersebut ditemukan & diperbarui,
     *              false jika BIB tidak ada di sheet.
     */
    public function updateRacepackByBib(
        string $bibNumber,
        string $status,
        string $time
    ): bool {
        $spreadsheetId = $this->spreadsheetId();
        $sheet = $this->quotedSheetName();

        // Cukup baca kolom D (BIB Number), bukan seluruh sheet.
        $response = $this->service()->spreadsheets_values->get(
            $spreadsheetId,
            "{$sheet}!D:D"
        );

        $rows = $response->getValues() ?? [];

        foreach ($rows as $index => $row) {
            if (! $this->sameBib((string) ($row[0] ?? ''), $bibNumber)) {
                continue;
            }

            // Index array mulai dari 0, nomor baris Sheets mulai dari 1.
            $sheetRow = $index + 1;

            $this->service()->spreadsheets_values->update(
                $spreadsheetId,
                "{$sheet}!F{$sheetRow}:G{$sheetRow}",
                new ValueRange(['values' => [[$status, $time]]]),
                ['valueInputOption' => 'USER_ENTERED']
            );

            return true;
        }

        return false;
    }

    /**
     * Bandingkan BIB. Jika sel di sheet bertipe angka, "09278" bisa tampil
     * sebagai "9278", jadi untuk BIB numerik angka 0 di depan diabaikan.
     */
    private function sameBib(string $sheetValue, string $bib): bool
    {
        $a = trim($sheetValue);
        $b = trim($bib);

        if ($a === '' || $b === '') {
            return false;
        }

        if (ctype_digit($a) && ctype_digit($b)) {
            return ltrim($a, '0') === ltrim($b, '0');
        }

        return $a === $b;
    }

    private function service(): Sheets
    {
        // Dibuat saat pertama dipakai saja, supaya request yang tidak
        // menyentuh Sheets tidak gagal karena kredensial belum ada.
        if ($this->service) {
            return $this->service;
        }

        $path = (string) config('services.google.credentials');

        if ($path === '') {
            throw new RuntimeException('GOOGLE_SHEETS_CREDENTIALS belum diisi di .env');
        }

        // Boleh path relatif dari root project atau path absolut.
        $file = is_file($path) ? $path : base_path($path);

        if (! is_file($file)) {
            throw new RuntimeException("File kredensial Google tidak ditemukan: {$file}");
        }

        $client = new Client();
        $client->setApplicationName('UC Color Run 2027');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAuthConfig($file);

        return $this->service = new Sheets($client);
    }

    private function spreadsheetId(): string
    {
        $id = (string) config('services.google.spreadsheet_id');

        if ($id === '') {
            throw new RuntimeException('GOOGLE_SHEETS_SPREADSHEET_ID belum diisi di .env');
        }

        return $id;
    }

    private function quotedSheetName(): string
    {
        $name = (string) config('services.google.sheet', 'Sheet1');

        return "'".str_replace("'", "''", $name)."'";
    }
}

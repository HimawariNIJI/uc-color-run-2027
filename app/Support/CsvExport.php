<?php

namespace App\Support;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams spreadsheet-friendly CSV downloads for the admin console.
 */
class CsvExport
{
    /**
     * Characters that make spreadsheet apps treat a cell as a formula.
     */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * @param  list<string>  $headers
     * @param  iterable<mixed>  $records
     * @param  Closure(mixed): list<string|int|null>  $toRow
     */
    public static function download(string $filename, array $headers, iterable $records, Closure $toRow): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $records, $toRow): void {
            $output = fopen('php://output', 'w');

            // A byte order mark lets Excel read names with accents correctly.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers, escape: '');

            foreach ($records as $record) {
                fputcsv($output, $toRow($record), escape: '');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Neutralize participant-entered text that a spreadsheet would run as a formula.
     */
    public static function text(?string $value): string
    {
        $value ??= '';

        return $value !== '' && in_array($value[0], self::FORMULA_TRIGGERS, true) ? "'".$value : $value;
    }

    /**
     * Timestamped file name, for example "uc-color-run-2027-users-20261101-0930.csv".
     */
    public static function filename(string $subject): string
    {
        return 'uc-color-run-2027-'.$subject.'-'.now()->format('Ymd-Hi').'.csv';
    }
}

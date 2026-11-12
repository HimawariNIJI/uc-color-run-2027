<?php

use App\Exceptions\GoogleSheetsException;
use App\Support\GoogleSheets;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeGoogleSheets;

/**
 * Point the configuration at a key file with these contents.
 */
function useKeyFile(string $contents): void
{
    $path = tempnam(sys_get_temp_dir(), 'key');
    file_put_contents($path, $contents);
    config(['services.google_sheets.credentials' => $path]);
}

it('signs in with a JWT signed by the service account key and reuses the token', function () {
    $google = FakeGoogleSheets::install(withParticipantsTab: true);

    GoogleSheets::fromConfig()->sheets();
    GoogleSheets::fromConfig()->values("'Participants'!A:B");

    expect($google->tokenRequests)->toBe(1)
        ->and($google->calls)->toHaveCount(2);
});

it('signs in again after Google stops accepting the token', function () {
    $google = FakeGoogleSheets::install(withParticipantsTab: true);
    $client = GoogleSheets::fromConfig();
    $client->sheets();
    $google->failNext(401);

    expect(fn () => $client->sheets())->toThrow(GoogleSheetsException::class, 'Google Sheets answered HTTP 401');

    $client->sheets();

    expect($google->tokenRequests)->toBe(2);
});

it('sends values RAW and only asks for the spreadsheet fields it needs', function () {
    FakeGoogleSheets::install(withParticipantsTab: true);
    $client = GoogleSheets::fromConfig();

    $client->sheets();
    $client->updateValues([['range' => "'Participants'!A1:B1", 'values' => [['a', 'b']]]]);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && str_contains($request->url(), 'fields=sheets'));
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/values:batchUpdate') && $request['valueInputOption'] === 'RAW');
});

it('sends nothing when there is nothing to write', function () {
    $google = FakeGoogleSheets::install(withParticipantsTab: true);
    $client = GoogleSheets::fromConfig();

    $client->updateValues([]);

    expect($google->calls)->toBe([])
        ->and($google->tokenRequests)->toBe(0);
});

it('reports Google errors with only the status and Google\'s short message, and knows which are temporary', function (int $status, bool $temporary) {
    $google = FakeGoogleSheets::install(withParticipantsTab: true);
    $google->failNext($status);

    try {
        GoogleSheets::fromConfig()->sheets();
        $this->fail('Expected a GoogleSheetsException.');
    } catch (GoogleSheetsException $exception) {
        expect($exception->getMessage())->toBe("Google Sheets answered HTTP {$status}: Simulated Google error")
            ->and($exception->status)->toBe($status)
            ->and($exception->isTemporary())->toBe($temporary);
    }
})->with([
    'rate limit' => [429, true],
    'server error' => [500, true],
    'unavailable' => [503, true],
    'bad request' => [400, false],
    'no permission' => [403, false],
    'not found' => [404, false],
]);

it('treats a lost connection as temporary', function () {
    $google = FakeGoogleSheets::install(withParticipantsTab: true);
    $google->loseConnectionNext();

    expect(fn () => GoogleSheets::fromConfig()->sheets())->toThrow(function (GoogleSheetsException $exception) {
        expect($exception->getMessage())->toBe('Could not reach Google Sheets.')
            ->and($exception->isTemporary())->toBeTrue();
    });
});

it('reports a refused sign-in without the key or the signed request', function () {
    $google = FakeGoogleSheets::install(withParticipantsTab: true);
    $google->refuseSignIn = true;

    expect(fn () => GoogleSheets::fromConfig()->sheets())->toThrow(function (GoogleSheetsException $exception) {
        expect($exception->getMessage())->toBe('Google refused the service account sign-in (HTTP 400): invalid_grant Invalid JWT Signature.')
            ->not->toContain('PRIVATE KEY')
            ->not->toContain('assertion')
            ->and($exception->isTemporary())->toBeFalse();
    });

    expect($google->calls)->toBe([]);
});

it('is configured only when both settings are filled in', function () {
    expect(GoogleSheets::isConfigured())->toBeFalse();

    FakeGoogleSheets::install();

    expect(GoogleSheets::isConfigured())->toBeTrue()
        ->and(GoogleSheets::configurationProblem())->toBeNull();
});

it('explains exactly what is wrong with the configuration, without anything from inside the key file', function (Closure $configure, string $problem) {
    FakeGoogleSheets::install();
    $configure();

    expect(GoogleSheets::configurationProblem())->toContain($problem)
        ->not->toContain('SECRET')
        ->and(fn () => GoogleSheets::fromConfig())->toThrow(GoogleSheetsException::class, 'Google Sheets is not configured correctly: ');
})->with([
    'no spreadsheet ID' => [fn () => config(['services.google_sheets.spreadsheet_id' => '']), 'GOOGLE_SHEETS_SPREADSHEET_ID is empty.'],
    'the whole spreadsheet URL' => [
        fn () => config(['services.google_sheets.spreadsheet_id' => 'https://docs.google.com/spreadsheets/d/abc/edit']),
        'GOOGLE_SHEETS_SPREADSHEET_ID does not look like a spreadsheet ID. Use the long ID between /d/ and /edit in the spreadsheet URL.',
    ],
    'no key file' => [fn () => config(['services.google_sheets.credentials' => '']), 'GOOGLE_APPLICATION_CREDENTIALS is empty.'],
    'a key file that does not exist' => [
        fn () => config(['services.google_sheets.credentials' => 'secrets/missing.json']),
        'the key file secrets/missing.json (GOOGLE_APPLICATION_CREDENTIALS) does not exist.',
    ],
    'a key file inside public/' => [
        fn () => config(['services.google_sheets.credentials' => 'public/google-service-account.json']),
        'the key file public/google-service-account.json is inside public/, where anyone could download it. Move it to secrets/.',
    ],
    'a key file that is not JSON' => [fn () => useKeyFile('SECRET-not-json'), 'is not valid JSON.'],
    'a key for a person instead of a service account' => [
        fn () => useKeyFile(json_encode(['type' => 'authorized_user', 'client_secret' => 'SECRET-VALUE-123', 'refresh_token' => 'SECRET-REFRESH'])),
        'is not a service account key (it needs type, client_email and private_key).',
    ],
    'a damaged private key' => [
        fn () => useKeyFile(json_encode(['type' => 'service_account', 'client_email' => 'sheets@example.iam.gserviceaccount.com', 'private_key' => "-----BEGIN PRIVATE KEY-----\nSECRET-DAMAGED\n-----END PRIVATE KEY-----\n"])),
        'cannot be read.',
    ],
]);

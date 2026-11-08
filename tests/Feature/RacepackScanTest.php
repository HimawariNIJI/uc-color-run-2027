<?php

use App\Jobs\SyncRacepackToSheet;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeParticipant(array $overrides = []): Participant
{
    return Participant::create(array_merge([
        'registration_id' => 'UCR-2601',
        'name' => 'Peserta Testing',
        'email' => 'testing@example.com',
        'bib_number' => '10020118',
        'category' => '5K',
        'qr_token' => (string) Str::uuid(),
    ], $overrides));
}

it('marks the racepack as collected and queues the Google Sheets sync', function () {
    Queue::fake();
    $participant = makeParticipant();

    $this->postJson(route('racepack.scan'), ['bib_number' => '10020118'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.bib_number', '10020118')
        ->assertJsonCount(1, 'recent');

    expect($participant->fresh()->racepack_collected)->toBeTrue()
        ->and($participant->fresh()->collected_at)->not->toBeNull();

    Queue::assertPushed(SyncRacepackToSheet::class, fn ($job) => $job->participantId === $participant->id);
});

it('trims whitespace around the scanned code', function () {
    Queue::fake();
    makeParticipant();

    $this->postJson(route('racepack.scan'), ['bib_number' => " 10020118\n"])->assertOk();
});

it('returns 404 for an unknown BIB number', function () {
    Queue::fake();

    $this->postJson(route('racepack.scan'), ['bib_number' => '99999999'])
        ->assertNotFound()
        ->assertJsonPath('status', 'not_found');

    Queue::assertNothingPushed();
});

it('rejects a second scan of the same QR code', function () {
    Queue::fake();
    makeParticipant();

    $this->postJson(route('racepack.scan'), ['bib_number' => '10020118'])->assertOk();

    $this->postJson(route('racepack.scan'), ['bib_number' => '10020118'])
        ->assertStatus(409)
        ->assertJsonPath('status', 'already_collected');

    Queue::assertPushed(SyncRacepackToSheet::class, 1);
});

it('requires a bib_number', function () {
    $this->postJson(route('racepack.scan'), [])->assertUnprocessable();
});

it('lists the most recent scans first', function () {
    Queue::fake();
    makeParticipant(['registration_id' => 'UCR-1', 'bib_number' => '10000001']);
    makeParticipant(['registration_id' => 'UCR-2', 'bib_number' => '10000002']);

    $this->postJson(route('racepack.scan'), ['bib_number' => '10000001']);
    $this->travel(1)->minutes();
    $this->postJson(route('racepack.scan'), ['bib_number' => '10000002']);

    $this->getJson(route('racepack.recent'))
        ->assertOk()
        ->assertJsonPath('data.0.registration_id', 'UCR-2')
        ->assertJsonPath('data.1.registration_id', 'UCR-1');
});

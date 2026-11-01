<?php

use App\Models\Admin;
use App\Models\Registration;
use App\Models\User;

it('downloads the accounts matching the search as CSV', function () {
    $this->travelTo('2026-10-21 10:42:00');
    Registration::factory()->paid()
        ->for(User::factory()->create(['name' => 'Rina Wijaya', 'email' => 'rina@example.com', 'google_id' => 'g-1', 'last_login_at' => now()]))
        ->create(['code' => 'UCR-2027-ABCDE', 'bib_number' => '50012']);
    User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.com']);

    $response = $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('admin.users.export', ['search' => 'rina']))
        ->assertOk()
        ->assertDownload('uc-color-run-2027-users-20261021-1042.csv')
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)
        ->toStartWith("\xEF\xBB\xBF")
        ->toContain('Name,Email,Authentication,"Last Login","Account Created","Registration ID","Registration Status","BIB Number"')
        ->toContain('"Rina Wijaya",rina@example.com,"Google Sign-In","2026-10-21 10:42","2026-10-21 10:42",UCR-2027-ABCDE,Registered,50012')
        ->not->toContain('Budi Santoso');
});

it('neutralises names that a spreadsheet would run as a formula', function () {
    User::factory()->create(['name' => '=HYPERLINK("http://evil.test")']);

    $csv = $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('admin.users.export'))
        ->streamedContent();

    expect($csv)->toContain('"\'=HYPERLINK(""http://evil.test"")"');
});

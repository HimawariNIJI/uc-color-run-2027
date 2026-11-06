<?php

use App\Models\Admin;
use App\Models\Registration;

it('downloads the registrations matching the filters as CSV, without identity numbers', function () {
    $this->travelTo('2026-10-21 10:42:00');
    Registration::factory()->paid()->create([
        'code' => 'UCR-2027-ABCDE',
        'full_name' => 'Rina Wijaya',
        'category' => '5k',
        'type' => 'internal',
        'identity_number' => '3578011205990001',
        'amount' => 125000,
        'transaction_id' => 'PAY-UCR-2027-ABCDE',
        'bib_number' => '50012',
    ]);
    Registration::factory()->create(['full_name' => 'Budi Santoso', 'category' => '5k']);

    $response = $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('admin.registrations.export', ['status' => 'paid', 'category' => '5k']))
        ->assertOk()
        ->assertDownload('uc-color-run-2027-registrations-20261021-1042.csv');

    $csv = $response->streamedContent();

    expect($csv)
        ->toContain('"Registration ID","Registration Type","Race Category","Full Name"')
        ->toContain('UCR-2027-ABCDE,Internal,5K,"Rina Wijaya"')
        ->toContain('125000,Paid,"2026-10-21 10:42","2026-10-21 10:42",PAY-UCR-2027-ABCDE,50012,')
        ->not->toContain('KTP')
        ->not->toContain('3578011205990001')
        ->not->toContain('Budi Santoso');
});

it('neutralises participant text that a spreadsheet would run as a formula', function () {
    Registration::factory()->create(['address' => '@SUM(1+1)*cmd']);

    $csv = $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('admin.registrations.export'))
        ->streamedContent();

    expect($csv)->toContain("'@SUM(1+1)*cmd");
});

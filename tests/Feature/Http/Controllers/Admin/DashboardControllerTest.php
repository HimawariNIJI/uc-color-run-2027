<?php

use App\Models\Admin;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->admin = Admin::factory()->create(['name' => 'Panitia Racepack']);
});

it('summarises accounts, registrations, payments and collected BIBs', function () {
    User::factory()->count(3)->create();
    Registration::factory()->paid()->create();
    Registration::factory()->bibCollected()->create();
    Registration::factory()->create();
    Registration::factory()->failed()->create();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Panitia Racepack')
        ->assertSeeInOrder([
            'Total Users', '7', 'Accounts that have logged in',
            'Total Registrations', '4', '57,1% of logged-in users',
            'Paid Registrations', '2', '50% of registrations',
            'BIB Issued', '1', '1 paid registrations awaiting BIB',
        ]);
});

it('shows zero percentages before anyone registers', function () {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(['0% of logged-in users', '0% of registrations', 'Belum ada registrasi.']);
});

it('counts registrations per race category and payment status', function () {
    Registration::factory()->count(2)->paid()->create(['category' => '5k']);
    Registration::factory()->create(['category' => '10k']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertViewHas('registrationsByCategory', [
            ['label' => '3K', 'value' => 0],
            ['label' => '5K', 'value' => 2],
            ['label' => '10K', 'value' => 1],
        ])
        ->assertViewHas('paymentOverview', [
            ['label' => 'Paid', 'value' => 2, 'tone' => 'success'],
            ['label' => 'Unpaid', 'value' => 1, 'tone' => 'warning'],
            ['label' => 'Failed', 'value' => 0, 'tone' => 'error'],
        ]);
});

it('charts new registrations per day for the chosen range', function (?string $range, int $days) {
    $this->travelTo('2026-11-20 15:00:00');
    Registration::factory()->count(2)->create(['created_at' => '2026-11-20 08:00:00']);
    Registration::factory()->create(['created_at' => '2026-11-18 23:30:00']);
    Registration::factory()->create(['created_at' => '2026-08-01 10:00:00']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.dashboard', array_filter(['range' => $range])))
        ->assertOk()
        ->assertSee("New registrations over the last {$days} days")
        ->assertViewHas('trend', function (Collection $trend) use ($days): bool {
            return $trend->count() === $days
                && $trend->first()['date']->toDateString() === now()->subDays($days - 1)->toDateString()
                && $trend->last()['value'] === 2
                && $trend[$days - 3]['value'] === 1
                && $trend->sum('value') === 3;
        });
})->with([
    'default' => [null, 7],
    '30 days' => ['30', 30],
    '90 days' => ['90', 90],
    'unsupported range' => ['365', 7],
]);

it('lists the five newest registrations', function () {
    $oldest = Registration::factory()->create(['full_name' => 'Peserta Terlama', 'created_at' => now()->subDays(10)]);
    Registration::factory()->count(5)->create(['created_at' => now()->subDay()]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertViewHas('recentRegistrations', fn (Collection $recent): bool => $recent->count() === 5)
        ->assertDontSee($oldest->full_name);
});

it('filters recent registrations by search term and payment status', function () {
    $match = Registration::factory()->paid()->create(['full_name' => 'Rina Wijaya']);
    Registration::factory()->create(['full_name' => 'Rina Pratiwi']);
    Registration::factory()->paid()->create(['full_name' => 'Budi Santoso']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.dashboard', ['search' => 'rina', 'status' => 'paid']))
        ->assertOk()
        ->assertSee($match->code)
        ->assertDontSee(['Rina Pratiwi', 'Budi Santoso'])
        ->assertSee(route('admin.registrations.index', ['search' => 'rina', 'status' => 'paid']));
});

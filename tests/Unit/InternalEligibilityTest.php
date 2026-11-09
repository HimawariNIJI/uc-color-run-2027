<?php

use App\Support\InternalEligibility;

it('accepts ciputra.ac.id and every subdomain of it', function (string $email) {
    expect(InternalEligibility::isUcEmail($email))->toBeTrue();
})->with([
    'user@ciputra.ac.id',
    'user@student.ciputra.ac.id',
    'user@magister.ciputra.ac.id',
    'user@alumni.ciputra.ac.id',
    'user@other.ciputra.ac.id',
    'user@deep.sub.ciputra.ac.id',
    'upper case' => 'User@Student.CIPUTRA.AC.ID',
    'surrounding spaces' => '  user@ciputra.ac.id ',
]);

it('rejects every other domain, including look-alikes', function (?string $email) {
    expect(InternalEligibility::isUcEmail($email))->toBeFalse();
})->with([
    'user@gmail.com',
    'user@yahoo.com',
    'user@company.com',
    'user@notciputra.ac.id',
    'user@exampleciputra.ac.id',
    'user@ciputra.ac.id.example.com',
    'user@ciputra.ac.id.',
    'user@ciputra.ac',
    'domain as the local part' => 'ciputra.ac.id@gmail.com',
    'two @ signs' => 'user@gmail.com@ciputra.ac.id',
    'no local part' => '@ciputra.ac.id',
    'no @ sign' => 'ciputra.ac.id',
    'empty' => '',
    'missing' => null,
]);

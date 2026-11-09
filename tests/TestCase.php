<?php

namespace Tests;

use Database\Seeders\TicketPriceSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every test database starts with the official registration packages.
     *
     * @var bool
     */
    protected $seed = true;

    /**
     * @var class-string
     */
    protected $seeder = TicketPriceSeeder::class;
}

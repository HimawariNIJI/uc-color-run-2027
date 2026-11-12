<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A package's BIB sequence cannot give out the numbers asked for: every prefix stops at 9999,
 * because the sequence part of a BIB has four digits.
 */
class BibNumbersExhausted extends RuntimeException
{
    public function __construct(public readonly string $prefix, public readonly int $requested = 1)
    {
        parent::__construct("BIB prefix {$prefix} has fewer than {$requested} numbers left (the sequence stops at 9999).");
    }
}

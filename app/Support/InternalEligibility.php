<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Decides which email addresses may register in the Internal category: Universitas Ciputra
 * addresses on ciputra.ac.id or any of its subdomains (student., magister., alumni., ...).
 */
class InternalEligibility
{
    public const UC_DOMAIN = 'ciputra.ac.id';

    /**
     * The domain must equal ciputra.ac.id or end with ".ciputra.ac.id" (case-insensitive), so
     * look-alikes such as notciputra.ac.id or ciputra.ac.id.example.com never qualify.
     */
    public static function isUcEmail(?string $email): bool
    {
        $email = Str::lower(trim((string) $email));

        if (substr_count($email, '@') !== 1 || str_starts_with($email, '@')) {
            return false;
        }

        $domain = Str::after($email, '@');

        return $domain === self::UC_DOMAIN || str_ends_with($domain, '.'.self::UC_DOMAIN);
    }
}

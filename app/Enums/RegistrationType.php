<?php

namespace App\Enums;

enum RegistrationType: string
{
    case Internal = 'internal';
    case External = 'external';
    case Bundling = 'bundling';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal',
            self::External => 'External',
            self::Bundling => 'Bundling',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Internal => 'Untuk mahasiswa, dosen, staf, dan alumni Universitas Ciputra.',
            self::External => 'Untuk masyarakat umum di luar Universitas Ciputra.',
            self::Bundling => 'Paket registrasi bundling dengan harga khusus.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Internal => 'building',
            self::External => 'globe',
            self::Bundling => 'package',
        };
    }
}

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
            self::Bundling => 'Family Bundle 3K (maks. 4 peserta) atau Couple Bundle 5K (maks. 2 peserta).',
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

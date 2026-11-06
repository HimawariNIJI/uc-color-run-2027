<?php

namespace App\Enums;

enum TshirtSize: string
{
    case ExtraSmall = 'XS';
    case Small = 'S';
    case Medium = 'M';
    case Large = 'L';
    case ExtraLarge = 'XL';
    case DoubleExtraLarge = 'XXL';
    case TripleExtraLarge = '3XL';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $size): array => [$size->value => $size->label()])->all();
    }
}

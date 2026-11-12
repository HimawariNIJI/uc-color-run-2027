<?php

namespace App\Enums;

enum RaceCategory: string
{
    case ThreeK = '3k';
    case FiveK = '5k';
    case TenK = '10k';

    public function label(): string
    {
        return match ($this) {
            self::ThreeK => '3K',
            self::FiveK => '5K',
            self::TenK => '10K',
        };
    }

    public function distance(): string
    {
        return match ($this) {
            self::ThreeK => '3 KM',
            self::FiveK => '5 KM',
            self::TenK => '10 KM',
        };
    }

    public function runLabel(): string
    {
        return $this->label().' Run';
    }

    /**
     * Race details configured for this category.
     *
     * @return array{start_time: string, cut_off: string, tagline: string, route_image: ?string, photo: string}
     */
    public function details(): array
    {
        return config("event.categories.{$this->value}");
    }
}

<?php

namespace App\Support;

/**
 * Indonesian provinces and their regencies/cities, used by the address fields.
 */
class IndonesiaRegions
{
    /**
     * @var array<string, list<string>>|null
     */
    private static ?array $regions = null;

    /**
     * Every province mapped to its sorted regency/city labels.
     *
     * @return array<string, list<string>>
     */
    public static function all(): array
    {
        return self::$regions ??= self::load();
    }

    /**
     * @return list<string>
     */
    public static function provinces(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return list<string>
     */
    public static function citiesOf(?string $province): array
    {
        return self::all()[$province] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function load(): array
    {
        /** @var array<string, array{kabupaten: list<string>, kota: list<string>}> $data */
        $data = json_decode(
            file_get_contents(resource_path('data/indonesia-regions.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        ksort($data);

        return array_map(function (array $groups): array {
            $cities = [
                ...array_map(fn (string $name): array => [$name, "Kabupaten {$name}"], $groups['kabupaten']),
                ...array_map(fn (string $name): array => [$name, "Kota {$name}"], $groups['kota']),
            ];

            usort($cities, fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

            return array_column($cities, 1);
        }, $data);
    }
}

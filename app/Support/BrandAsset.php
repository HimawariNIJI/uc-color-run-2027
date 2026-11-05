<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Brand images from the Design Division, served from public/assets/images.
 * Filenames contain spaces and "@", so URLs are encoded here.
 */
class BrandAsset
{
    private const DIRECTORY = 'assets/images';

    /**
     * @var array<string, array{0: int, 1: int}>
     */
    private static array $dimensions = [];

    public static function url(string $file): string
    {
        return asset(self::DIRECTORY.'/'.rawurlencode($file));
    }

    /**
     * Intrinsic width and height, so images reserve their space before they load.
     *
     * @return array{0: int, 1: int}
     */
    public static function dimensions(string $file): array
    {
        if (isset(self::$dimensions[$file])) {
            return self::$dimensions[$file];
        }

        $size = @getimagesize(public_path(self::DIRECTORY.'/'.$file));

        if ($size === false) {
            throw new InvalidArgumentException("Unknown brand image [{$file}].");
        }

        return self::$dimensions[$file] = [$size[0], $size[1]];
    }
}

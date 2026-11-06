<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Official media from the Design and Documentation divisions, served from public/assets.
 * Filenames contain spaces, brackets, "&" and "@", so URLs are encoded here.
 */
class BrandAsset
{
    public const IMAGES = 'assets/images';

    /**
     * @var array<string, array{0: int, 1: int}>
     */
    private static array $dimensions = [];

    public static function url(string $file, string $directory = self::IMAGES): string
    {
        return self::publicUrl($directory.'/'.$file);
    }

    /**
     * URL for a path relative to public/, with every segment encoded.
     */
    public static function publicUrl(string $path): string
    {
        return asset(implode('/', array_map(rawurlencode(...), explode('/', $path))));
    }

    /**
     * Intrinsic width and height, so images reserve their space before they load.
     *
     * @return array{0: int, 1: int}
     */
    public static function dimensions(string $file, string $directory = self::IMAGES): array
    {
        $key = $directory.'/'.$file;

        if (isset(self::$dimensions[$key])) {
            return self::$dimensions[$key];
        }

        $size = @getimagesize(public_path($key));

        if ($size === false) {
            throw new InvalidArgumentException("Unknown brand image [{$key}].");
        }

        return self::$dimensions[$key] = [$size[0], $size[1]];
    }
}

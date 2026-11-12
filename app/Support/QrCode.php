<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    /**
     * Render the given payload as an inline SVG QR code: black modules on white, the same image for
     * the same payload. The error correction level defaults to the library's lowest (L), which the
     * Midtrans QRIS uses; racepack QRs ask for a higher one.
     */
    public static function svg(string $payload, int $size = 240, ?ErrorCorrectionLevel $errorCorrection = null, int $margin = 1): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, $margin), new SvgImageBackEnd));

        return trim((string) preg_replace('/^<\?xml[^>]*\?>/', '', $writer->writeString($payload, Encoder::DEFAULT_BYTE_MODE_ENCODING, $errorCorrection)));
    }
}

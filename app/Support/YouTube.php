<?php

namespace App\Support;

/**
 * Turns a YouTube link into an embeddable player URL.
 */
class YouTube
{
    /**
     * Video IDs are 11 characters, in a share (youtu.be), watch, embed, shorts or live link.
     */
    private const LINK_PATTERN = '~^(?:https?://)?(?:www\.|m\.)?(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:\S*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})(?![A-Za-z0-9_-])~';

    /**
     * Privacy-enhanced player URL (no tracking cookies until the visitor presses play),
     * or null when the link is empty or not a YouTube video.
     */
    public static function embedUrl(?string $link): ?string
    {
        if (blank($link) || ! preg_match(self::LINK_PATTERN, trim($link), $matches)) {
            return null;
        }

        return "https://www.youtube-nocookie.com/embed/{$matches[1]}?rel=0";
    }
}

<?php

use App\Support\YouTube;

it('turns any kind of YouTube link into the privacy-enhanced player', function (string $link) {
    expect(YouTube::embedUrl($link))->toBe('https://www.youtube-nocookie.com/embed/HEm8BXHGUTE?rel=0');
})->with([
    'share link' => 'https://youtu.be/HEm8BXHGUTE',
    'share link with tracking' => 'https://youtu.be/HEm8BXHGUTE?si=AbCdEf123',
    'watch link' => 'https://www.youtube.com/watch?v=HEm8BXHGUTE',
    'watch link with other parameters first' => 'https://www.youtube.com/watch?feature=shared&v=HEm8BXHGUTE&t=30s',
    'mobile link' => 'https://m.youtube.com/watch?v=HEm8BXHGUTE',
    'embed link' => 'https://www.youtube.com/embed/HEm8BXHGUTE',
    'shorts link' => 'https://youtube.com/shorts/HEm8BXHGUTE',
    'without scheme, with spaces' => '  youtu.be/HEm8BXHGUTE ',
]);

it('ignores links that are not a YouTube video', function (?string $link) {
    expect(YouTube::embedUrl($link))->toBeNull();
})->with([
    'empty' => null,
    'blank' => '',
    'another site' => 'https://vimeo.com/123456789',
    'look-alike domain' => 'https://notyoutube.com/watch?v=HEm8BXHGUTE',
    'channel page' => 'https://www.youtube.com/@uccolorrun',
    'ID too long' => 'https://youtu.be/HEm8BXHGUTEX',
]);

<?php

test('the event pages are available', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('RACE CATEGORY');

    $this->get('/schedule')
        ->assertOk()
        ->assertSee('Race Schedule');

    $this->get('/regulation')
        ->assertOk()
        ->assertSee('Race Regulation');

    $this->get('/regulation?doc=privacy')
        ->assertOk()
        ->assertSee('Privacy Policy');
});

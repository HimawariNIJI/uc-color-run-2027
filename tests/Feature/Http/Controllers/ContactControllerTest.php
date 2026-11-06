<?php

use App\Models\ContactMessage;

it('stores a contact message and confirms it was sent', function () {
    $this->post(route('contact.store'), [
        'name' => 'Rina Wijaya',
        'email' => 'rina@example.com',
        'message' => 'Apakah anak usia 10 tahun boleh ikut kategori 3K?',
    ])
        ->assertRedirect(route('contact'))
        ->assertSessionHas('success');

    expect(ContactMessage::sole())
        ->name->toBe('Rina Wijaya')
        ->email->toBe('rina@example.com');
});

it('rejects an empty contact message with Indonesian errors', function () {
    $this->post(route('contact.store'), [])
        ->assertSessionHasErrors([
            'name' => 'Full Name wajib diisi.',
            'email' => 'Email Address wajib diisi.',
            'message' => 'Message wajib diisi.',
        ]);

    expect(ContactMessage::count())->toBe(0);
});

it('rejects an invalid email address', function () {
    $this->post(route('contact.store'), [
        'name' => 'Rina Wijaya',
        'email' => 'not-an-email',
        'message' => 'Halo panitia, saya ingin bertanya.',
    ])->assertSessionHasErrors(['email' => 'Format Email Address tidak valid.']);
});

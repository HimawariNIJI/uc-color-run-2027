<?php

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

beforeEach(function () {
    config([
        'mail.contact_us.address' => 'panitia@example.test',
        'mail.from' => ['address' => 'noreply@uccolorrun.test', 'name' => 'UC Color Run 2027'],
    ]);

    $this->participant = User::factory()->create(['name' => 'Rina Wijaya', 'email' => 'rina@example.com']);
});

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function contactPayload(array $overrides = []): array
{
    return [
        'name' => 'Rina Wijaya',
        'message' => "Halo panitia,\nApakah anak usia 10 tahun boleh ikut kategori 3K?",
        ...$overrides,
    ];
}

it('asks guests to sign in with Google instead of showing the form', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Sign in with Google')
        ->assertSee('href="'.route('auth.google', ['return_to' => '/contact']).'"', false)
        ->assertDontSee('action="'.route('contact.store').'"', false)
        ->assertDontSee('name="message"', false);
});

it('shows signed-in participants the form with their Google name and a read-only email', function () {
    $this->actingAs($this->participant)
        ->get(route('contact'))
        ->assertOk()
        ->assertDontSee('Sign in with Google')
        ->assertSee('action="'.route('contact.store').'"', false)
        ->assertSee('value="Rina Wijaya"', false)
        ->assertSee('value="rina@example.com" readonly', false)
        ->assertSee('Diambil dari akun Google kamu dan tidak dapat diubah.')
        ->assertDontSee('name="email"', false);
});

it('emails the message with the typed name and the account email, and keeps a copy', function () {
    Mail::fake();

    $this->actingAs($this->participant)
        ->post(route('contact.store'), contactPayload(['name' => 'Rina W. dari Kelas 3B']))
        ->assertRedirect(route('contact'))
        ->assertSessionHas('success', 'Pesan kamu sudah terkirim. Panitia akan membalas melalui email secepatnya.');

    Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => $mail->hasTo('panitia@example.test')
        && $mail->hasReplyTo('rina@example.com', 'Rina W. dari Kelas 3B'));
    Mail::assertSentCount(1);
    expect(ContactMessage::sole())
        ->name->toBe('Rina W. dari Kelas 3B')
        ->email->toBe('rina@example.com');
});

it('sends from the application mail account with the participant as reply-to', function () {
    config(['mail.default' => 'array']);
    $this->travelTo(Carbon::parse('2026-11-08 14:30:00'));

    $this->actingAs($this->participant)->post(route('contact.store'), contactPayload())->assertSessionHas('success');

    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
    expect($email->getFrom()[0]->toString())->toBe('"UC Color Run 2027" <noreply@uccolorrun.test>')
        ->and($email->getTo()[0]->getAddress())->toBe('panitia@example.test')
        ->and($email->getReplyTo()[0]->toString())->toBe('"Rina Wijaya" <rina@example.com>')
        ->and($email->getSubject())->toBe('UC Color Run 2027 - Contact Us Message')
        ->and($email->getTextBody())->toContain(
            'Nama    : Rina Wijaya',
            'Email   : rina@example.com',
            'Dikirim : Minggu, 8 November 2026, 14.30 WIB',
            "Halo panitia,\nApakah anak usia 10 tahun boleh ikut kategori 3K?",
        );
});

it('uses the same subject for every message, whoever sends it', function () {
    config(['mail.default' => 'array']);
    $otherParticipant = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@student.ciputra.ac.id']);

    $this->actingAs($this->participant)->post(route('contact.store'), contactPayload());
    $this->actingAs($otherParticipant)->post(route('contact.store'), contactPayload(['name' => 'Budi', 'message' => 'Subjek: tolong balas cepat!']));

    $subjects = Mail::mailer('array')->getSymfonyTransport()->messages()
        ->map(fn ($sent) => $sent->getOriginalMessage()->getSubject())
        ->all();
    expect($subjects)->toBe(['UC Color Run 2027 - Contact Us Message', 'UC Color Run 2027 - Contact Us Message']);
});

it('ignores any email, recipient or sender address sent by the browser', function () {
    config(['mail.default' => 'array']);

    $this->actingAs($this->participant)->post(route('contact.store'), contactPayload([
        'email' => 'attacker@example.com',
        'to' => 'attacker@example.com',
        'recipient' => 'attacker@example.com',
        'from' => 'attacker@example.com',
    ]))->assertSessionHas('success');

    $email = Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
    expect(collect($email->getTo())->map->getAddress()->all())->toBe(['panitia@example.test'])
        ->and(collect($email->getFrom())->map->getAddress()->all())->toBe(['noreply@uccolorrun.test'])
        ->and(collect($email->getReplyTo())->map->getAddress()->all())->toBe(['rina@example.com'])
        ->and($email->getCc())->toBeEmpty()
        ->and($email->getBcc())->toBeEmpty()
        ->and($email->getTextBody())->not->toContain('attacker@example.com')
        ->and(ContactMessage::sole()->email)->toBe('rina@example.com');
});

it('sends guests who post a message to the sign-in page without sending anything', function () {
    Mail::fake();

    $this->from(route('contact'))
        ->post(route('contact.store'), [...contactPayload(), 'email' => 'rina@example.com'])
        ->assertRedirect(route('login'));

    $this->assertGuest();
    Mail::assertNothingSent();
    expect(ContactMessage::count())->toBe(0)
        ->and(session('url.intended'))->toBe(route('contact'));
});

it('shows the sender, the message and the time sent, escaping what the participant typed', function () {
    $mail = new ContactMessageReceived(
        'Rina <b>Wijaya</b>',
        'rina@example.com',
        "Baris satu <script>alert(1)</script>\nBaris dua",
        Carbon::parse('2026-11-08 14:30:00'),
    );

    $mail->assertSeeInHtml('Rina &lt;b&gt;Wijaya&lt;/b&gt;', false)
        ->assertSeeInHtml('mailto:rina@example.com', false)
        ->assertSeeInHtml('Baris satu &lt;script&gt;alert(1)&lt;/script&gt;<br />', false)
        ->assertDontSeeInHtml('<script>alert(1)</script>', false)
        ->assertSeeInHtml('Minggu, 8 November 2026, 14.30 WIB')
        ->assertSeeInText('Baris dua');
});

it('shows an error and keeps the form when the email cannot be sent', function () {
    Exceptions::fake();
    Mail::shouldReceive('to')->once()->andThrow(new TransportException('Connection could not be established with host "smtp.secret-host.test"'));

    $this->actingAs($this->participant)
        ->post(route('contact.store'), contactPayload())
        ->assertRedirect(route('contact'))
        ->assertSessionMissing('success')
        ->assertSessionHas('error', 'Maaf, pesan kamu belum terkirim karena ada gangguan pada sistem email kami. Silakan coba lagi beberapa saat lagi atau hubungi panitia melalui kanal di bawah.')
        ->assertSessionHasInput('message', contactPayload()['message']);

    $this->get(route('contact'))
        ->assertSee('Maaf, pesan kamu belum terkirim')
        ->assertDontSee('secret-host');

    Exceptions::assertReported(TransportException::class);
    expect(ContactMessage::count())->toBe(0);
});

it('treats a missing Contact Us recipient as a delivery failure', function () {
    Exceptions::fake();
    Mail::fake();
    config(['mail.contact_us.address' => null]);

    $this->actingAs($this->participant)
        ->post(route('contact.store'), contactPayload())
        ->assertSessionMissing('success')
        ->assertSessionHas('error');

    Mail::assertNothingSent();
    Exceptions::assertReported(fn (RuntimeException $exception): bool => str_contains($exception->getMessage(), 'CONTACT_US_EMAIL'));
    expect(ContactMessage::count())->toBe(0);
});

it('rejects an empty contact message with Indonesian errors', function () {
    Mail::fake();

    $this->actingAs($this->participant)
        ->post(route('contact.store'), [])
        ->assertSessionHasErrors([
            'name' => 'Full Name wajib diisi.',
            'message' => 'Message wajib diisi.',
        ]);

    Mail::assertNothingSent();
    expect(ContactMessage::count())->toBe(0);
});

it('rejects invalid contact details', function (array $overrides, string $field, string $message) {
    Mail::fake();

    $this->actingAs($this->participant)
        ->post(route('contact.store'), contactPayload($overrides))
        ->assertSessionHasErrors([$field => $message]);

    Mail::assertNothingSent();
})->with([
    'header injection in the name' => [['name' => "Rina\r\nBcc: spam@example.com"], 'name', 'Full Name tidak boleh berisi baris baru.'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name', 'Full Name maksimal 255 karakter.'],
    'message too long' => [['message' => str_repeat('a', 5001)], 'message', 'Message maksimal 5000 karakter.'],
]);

it('accepts the longest allowed name and message', function () {
    Mail::fake();

    $this->actingAs($this->participant)
        ->post(route('contact.store'), contactPayload(['name' => str_repeat('a', 255), 'message' => str_repeat('a', 5000)]))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
});

it('limits each participant to five messages a minute', function () {
    Mail::fake();
    $this->actingAs($this->participant);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('contact.store'), contactPayload())->assertRedirect(route('contact'));
    }

    $this->post(route('contact.store'), contactPayload())->assertTooManyRequests();
    Mail::assertSentCount(5);
});

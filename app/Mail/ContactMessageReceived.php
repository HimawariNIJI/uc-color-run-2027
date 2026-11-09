<?php

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A message from the Contact Us form for the committee inbox (mail.contact_us.address).
 * It is sent from the application's own mail account; replying goes straight to the visitor.
 */
class ContactMessageReceived extends Mailable
{
    use Queueable;

    /**
     * The form has no subject field, so every message uses the same subject.
     */
    public const SUBJECT = 'UC Color Run 2027 - Contact Us Message';

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $messageBody,
        public CarbonInterface $submittedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: self::SUBJECT,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-message',
            text: 'mail.contact-message-text',
        );
    }
}

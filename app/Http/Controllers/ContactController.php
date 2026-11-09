<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ContactController extends Controller
{
    public function create(): View
    {
        $contacts = config('event.contacts');
        $venue = config('event.venue');

        return view('pages.contact', [
            'channels' => [
                ['label' => 'Email', 'value' => $contacts['email'], 'url' => 'mailto:'.$contacts['email'], 'icon' => 'mail', 'external' => false],
                ['label' => 'WhatsApp', 'value' => $contacts['whatsapp']['display'], 'url' => $contacts['whatsapp']['url'], 'icon' => 'message-circle', 'external' => true],
                ['label' => 'Instagram', 'value' => $contacts['instagram']['display'], 'url' => $contacts['instagram']['url'], 'icon' => 'instagram', 'external' => true],
                ['label' => 'Lokasi', 'value' => $venue['name'], 'url' => $venue['maps_url'], 'icon' => 'map-pin', 'external' => true],
            ],
        ]);
    }

    /**
     * Email the message to the committee inbox (CONTACT_US_EMAIL), then keep a copy. The sender's
     * email is the signed-in participant's Google email; the name is the one they typed.
     * The email is sent right away rather than queued, so "sent" only shows once it really was.
     */
    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $contact = [...$request->validated(), 'email' => $request->user()->email];
        $recipient = config('mail.contact_us.address');

        if (blank($recipient)) {
            report(new RuntimeException('CONTACT_US_EMAIL is not set, so Contact Us messages have no recipient.'));

            return $this->deliveryFailed($request);
        }

        try {
            Mail::to($recipient)->send(new ContactMessageReceived($contact['name'], $contact['email'], $contact['message'], now()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->deliveryFailed($request);
        }

        ContactMessage::create($contact);

        return to_route('contact')->with('success', 'Pesan kamu sudah terkirim. Panitia akan membalas melalui email secepatnya.');
    }

    /**
     * Keep what the participant typed so they can try again; server details stay in the log.
     */
    private function deliveryFailed(StoreContactMessageRequest $request): RedirectResponse
    {
        return to_route('contact')
            ->withInput($request->only('name', 'message'))
            ->with('error', 'Maaf, pesan kamu belum terkirim karena ada gangguan pada sistem email kami. Silakan coba lagi beberapa saat lagi atau hubungi panitia melalui kanal di bawah.');
    }
}

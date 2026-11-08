<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
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

    public function store(StoreContactMessageRequest $request): RedirectResponse
{
    $contact = ContactMessage::create($request->validated());

    try {
        Mail::raw(
            "Nama: {$contact->name}\nEmail: {$contact->email}\n\nPesan:\n{$contact->message}",
            function ($mail) use ($contact) {
                $mail->to(config('mail.contact_to'))
                    ->subject('Pesan Contact Us - UC Color Run 2027')
                    ->replyTo($contact->email, $contact->name);
            }
        );

        $message = config('mail.default') === 'log'
            ? 'Pesan berhasil disimpan.'
            : 'Pesan berhasil dikirim ke panitia.';
    } catch (Throwable $exception) {
        report($exception);

        $message = 'Pesan tersimpan, tetapi email belum berhasil dikirim.';
    }

    return to_route('contact')->with('success', $message);
}
}

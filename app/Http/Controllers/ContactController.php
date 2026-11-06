<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
        ContactMessage::create($request->validated());

        return to_route('contact')->with('success', 'Pesan kamu sudah terkirim. Panitia akan membalas melalui email secepatnya.');
    }
}

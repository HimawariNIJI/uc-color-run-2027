<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    /**
     * Display the registration form.
     */
    public function create(): View
    {
        $registration = null;
        if (session('registered_id')) {
            $registration = Registration::find(session('registered_id'));
        }

        return view('registration.create', compact('registration'));
    }

    /**
     * Store a newly created registration.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone_number' => 'required|string|max:25',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|in:Male,Female',
            'ktp_passport' => 'required|string|max:50',
            'race_category' => 'required|string|in:5K Run,10K Run,21K Half Marathon',
            'tshirt_size' => 'required|string|in:XS,S,M,L,XL,XXL',
            'emergency_name' => 'required|string|max:100',
            'emergency_phone' => 'required|string|max:25',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'agreement' => 'accepted',
        ], [
            'agreement.accepted' => 'Anda harus menyetujui Peraturan Lomba dan Kebijakan Privasi.',
        ]);

        $amount = Registration::getPriceForCategory($validated['race_category']);
        $registrationCode = 'UCR-2027-'.strtoupper(Str::random(5));
        $orderId = 'UCRUN-'.date('YmdHis').'-'.rand(100, 999);

        // Pre-create Order for Midtrans payment
        Order::create([
            'order_id' => $orderId,
            'participant_name' => $validated['full_name'],
            'race_category' => $validated['race_category'],
            'tshirt_size' => $validated['tshirt_size'],
            'item_name' => "UC Color Run {$validated['race_category']}",
            'amount' => $amount,
            'status' => 'PENDING',
        ]);

        $registration = Registration::create([
            'registration_code' => $registrationCode,
            'user_id' => auth()->id(),
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'ktp_passport' => $validated['ktp_passport'],
            'race_category' => $validated['race_category'],
            'tshirt_size' => $validated['tshirt_size'],
            'emergency_name' => $validated['emergency_name'],
            'emergency_phone' => $validated['emergency_phone'],
            'province' => $validated['province'],
            'city' => $validated['city'],
            'address' => $validated['address'],
            'amount' => $amount,
            'payment_status' => 'Pending',
            'order_id' => $orderId,
            'agreement' => true,
        ]);

        // Keep active registration in session
        session(['registered_id' => $registration->id]);

        return redirect()->route('my-registration')
            ->with('success', 'Registrasi berhasil dikirim! Silakan selesaikan pembayaran untuk mengamankan slot.');
    }

    /**
     * Show form to edit registration before payment.
     */
    public function edit(int $id): View|RedirectResponse
    {
        $registration = Registration::findOrFail($id);

        if ($registration->payment_status === 'Paid') {
            return redirect()->route('my-registration')
                ->with('error', 'Registrasi yang sudah dibayar tidak dapat diedit langsung.');
        }

        return view('registration.edit', compact('registration'));
    }

    /**
     * Update existing registration.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $registration = Registration::findOrFail($id);

        if ($registration->payment_status === 'Paid') {
            return redirect()->route('my-registration')
                ->with('error', 'Registrasi yang sudah dibayar tidak dapat diubah.');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone_number' => 'required|string|max:25',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|in:Male,Female',
            'ktp_passport' => 'required|string|max:50',
            'race_category' => 'required|string|in:5K Run,10K Run,21K Half Marathon',
            'tshirt_size' => 'required|string|in:XS,S,M,L,XL,XXL',
            'emergency_name' => 'required|string|max:100',
            'emergency_phone' => 'required|string|max:25',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:500',
        ]);

        $amount = Registration::getPriceForCategory($validated['race_category']);

        $registration->update([
            ...$validated,
            'amount' => $amount,
        ]);

        // Update corresponding order
        if ($registration->order_id) {
            Order::where('order_id', $registration->order_id)->update([
                'participant_name' => $validated['full_name'],
                'race_category' => $validated['race_category'],
                'tshirt_size' => $validated['tshirt_size'],
                'item_name' => "UC Color Run {$validated['race_category']}",
                'amount' => $amount,
            ]);
        }

        return redirect()->route('my-registration')
            ->with('success', 'Data registrasi berhasil diperbarui!');
    }

    /**
     * Display My Registration (Empty, Pending, or Paid states).
     */
    public function myRegistration(Request $request): View
    {
        $registration = null;

        // 1. Check query param code/id
        if ($request->query('code')) {
            $registration = Registration::where('registration_code', $request->query('code'))->first();
        } elseif ($request->query('id')) {
            $registration = Registration::find($request->query('id'));
        }

        // 2. Check session
        if (! $registration && session('registered_id')) {
            $registration = Registration::find(session('registered_id'));
        }

        // 3. Check authenticated user
        if (! $registration && auth()->check()) {
            $registration = Registration::where('user_id', auth()->id())->latest()->first();
        }

        // If registration found, synchronize payment status with Order
        if ($registration && $registration->order_id) {
            $order = Order::where('order_id', $registration->order_id)->first();
            if ($order && $order->status === 'PAID' && $registration->payment_status !== 'Paid') {
                $registration->update([
                    'payment_status' => 'Paid',
                    'bib_number' => $registration->bib_number ?: ('UC27'.str_pad((string) $registration->id, 3, '0', STR_PAD_LEFT)),
                ]);
            }
        }

        return view('myregistration', compact('registration'));
    }

    /**
     * Display BIB view for paid registrations.
     */
    public function myBib(Request $request): View|RedirectResponse
    {
        $registration = null;
        if (session('registered_id')) {
            $registration = Registration::find(session('registered_id'));
        }

        if (! $registration && auth()->check()) {
            $registration = Registration::where('user_id', auth()->id())->latest()->first();
        }

        if (! $registration || $registration->payment_status !== 'Paid') {
            return redirect()->route('my-registration')
                ->with('error', 'Nomor BIB hanya dapat diakses setelah pembayaran terkonfirmasi.');
        }

        $bib = [
            'bib_number' => $registration->bib_number ?: 'UC27'.str_pad((string) $registration->id, 3, '0', STR_PAD_LEFT),
            'category' => $registration->race_category,
            'runner_name' => $registration->full_name,
            'wave' => $registration->race_category === '21K Half Marathon' ? 'Wave 1 (05:00 AM)' : ($registration->race_category === '10K Run' ? 'Wave 2 (05:30 AM)' : 'Wave 3 (06:00 AM)'),
        ];

        return view('bib', compact('registration', 'bib'));
    }
}

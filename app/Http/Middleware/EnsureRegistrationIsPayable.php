<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep unpaid registrations away from QRIS payment while their package has no price in the
 * database, so nobody is ever charged an unknown amount.
 */
class EnsureRegistrationIsPayable
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $registration = $request->user()?->registration;

        if ($registration !== null && ! $registration->isPaid() && ! $registration->hasAnnouncedPrice()) {
            return to_route('my-registration.show')
                ->with('info', 'Harga resmi paket registrasimu belum diumumkan, jadi pembayaran belum bisa dilakukan. Panitia akan memberi kabar setelah harga diumumkan.');
        }

        return $next($request);
    }
}

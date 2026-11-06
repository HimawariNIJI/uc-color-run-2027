<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Send participants without a registration to the (empty) My Registration page.
 */
class EnsureUserHasRegistration
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->registration === null) {
            return to_route('my-registration.show');
        }

        return $next($request);
    }
}

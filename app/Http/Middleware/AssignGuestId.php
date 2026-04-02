<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignGuestId
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if guest_uuid cookie already exists
        if (!$request->hasCookie('guest_uuid')) {
            // Generate a new UUID for the anonymous user
            $guestId = Str::uuid()->toString();

            // Attach the UUID as a cookie that expires in 24 hours (1440 minutes)
            $request->merge(['guest_uuid' => $guestId]);
        } else {
            // Use the existing guest_uuid from the cookie
            $request->merge(['guest_uuid' => $request->cookie('guest_uuid')]);
        }

        $response = $next($request);

        // Add the guest_uuid cookie to the response if it wasn't previously set
        if (!$request->hasCookie('guest_uuid')) {
            $response->cookie(
                'guest_uuid',
                $request->input('guest_uuid'),
                1440,
                '/',
                null,
                config('app.env') === 'production',
                false,
                false,
                'lax'
            );
        }

        return $response;
    }
}

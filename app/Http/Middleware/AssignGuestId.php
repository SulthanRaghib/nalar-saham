<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * AssignGuestId Middleware
 *
 * Assigns a persistent UUID cookie to anonymous visitors.
 * Used for tracking analysis history per-guest without authentication.
 */
class AssignGuestId
{
    private const COOKIE_NAME = 'guest_uuid';
    private const COOKIE_MINUTES = 43200; // 30 days

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only set cookie if it doesn't already exist
        if (!$request->hasCookie(self::COOKIE_NAME)) {
            $guestId = Str::uuid()->toString();

            $response->headers->setCookie(cookie(
                name: self::COOKIE_NAME,
                value: $guestId,
                minutes: self::COOKIE_MINUTES,
                path: '/',
                secure: config('app.env') === 'production',
                httpOnly: false,
                sameSite: 'lax',
            ));
        }

        return $response;
    }
}

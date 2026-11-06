<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencegah halaman ter-cache di browser sehingga setelah logout, tombol
 * "back" tidak menampilkan halaman ber-otentikasi dari cache.
 * (Carry-forward learning dari SI-HARBANG & Portfolio CMS.)
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}

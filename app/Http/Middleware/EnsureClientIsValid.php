<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->input('client_id') ?? $request->query('client_id');

        if ($clientId) {
            $client = Client::find($clientId);

            if (!$client || $client->revoked) {
                return response()->json([
                    'error' => 'invalid_client',
                    'message' => 'The client application has been revoked or does not exist.',
                ], Response::HTTP_UNAUTHORIZED);
            }
        }
        return $next($request);
    }
}

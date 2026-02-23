<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SsoAutoRedirect
{

    public function handle(Request $request, Closure $next): Response
    {
        
        if ($request->is('oauth/authorize') && Auth::check()) {
            return $next($request);
        }

        return $next($request);
    }
}
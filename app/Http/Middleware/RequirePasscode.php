<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasscode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('passcode_ok') === true) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(401);
        }

        return redirect()->guest(route('login'));
    }
}

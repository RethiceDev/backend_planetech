<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
{
    // Test de débogage
    //dd(Auth::guard('admin')->check());

    if (!Auth::guard('admin')->check()) {
        return redirect()->route('admin.login');
    }

    return $next($request);
}
}

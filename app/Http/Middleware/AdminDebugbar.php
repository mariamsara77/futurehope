<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminDebugbar
{
    // App\Http\Middleware\AdminDebugbar.php

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound(\Barryvdh\Debugbar\LaravelDebugbar::class)) {
            // Disable by default, enable only for User ID 1
            $isAdmin = auth()->check() && auth()->id() === 1;

            if ($isAdmin) {
                \Barryvdh\Debugbar\Facades\Debugbar::enable();
            } else {
                \Barryvdh\Debugbar\Facades\Debugbar::disable();
            }
        }

        return $next($request);
    }
}
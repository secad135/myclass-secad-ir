<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->must_change_password) {
            $allowedRoutes = [
                'panel.profile.edit',
                'panel.profile.update',
                'panel.profile.password',
                'logout',
                'profile.edit',
                'profile.update',
                'password.update',
            ];

            if (!$request->routeIs(...$allowedRoutes)) {
                return redirect()->route('panel.profile.edit')
                    ->with('warning', 'برای ادامه، لطفاً ابتدا رمز خود را تغییر دهید.');
            }
        }

        return $next($request);
    }
}
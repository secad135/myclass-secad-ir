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
            $user = auth()->user();

            if ($user->is_teacher) {
                $allowedRoutes = [
                    'teacher.profile',
                    'teacher.profile.update',
                    'teacher.profile.password',
                    'logout',
                ];
                $redirectRoute = 'teacher.profile';
            } else {
                $allowedRoutes = [
                    'panel.profile.edit',
                    'panel.profile.update',
                    'panel.profile.password',
                    'logout',
                    'profile.edit',
                    'profile.update',
                    'password.update',
                ];
                $redirectRoute = 'panel.profile.edit';
            }

            if (!$request->routeIs(...$allowedRoutes)) {
                return redirect()->route($redirectRoute)
                    ->with('warning', 'برای ادامه، لطفاً ابتدا رمز خود را تغییر دهید.');
            }
        }

        return $next($request);
    }
}
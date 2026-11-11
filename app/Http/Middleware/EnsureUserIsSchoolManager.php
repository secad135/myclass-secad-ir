<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSchoolManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->is_school_manager) {
            abort(403, 'شما دسترسی به این بخش ندارید.');
        }

        return $next($request);
    }
}

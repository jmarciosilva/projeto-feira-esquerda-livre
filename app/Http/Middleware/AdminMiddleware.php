<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || ! $user->is_active || ! $user->isInternalUser()) {
            abort(403, 'Acesso não autorizado.');
        }

        if (! $user->isAdmin() && ! $user->can('admin.acessar')) {
            abort(403, 'Acesso não autorizado.');
        }

        return $next($request);
    }
}

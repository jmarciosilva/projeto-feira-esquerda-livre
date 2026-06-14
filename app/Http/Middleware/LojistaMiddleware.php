<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LojistaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->role === UserRole::Admin || $user->role === UserRole::Editor) {
            return $next($request);
        }

        if ($user->role !== UserRole::Lojista) {
            abort(403, 'Acesso restrito a lojistas.');
        }

        if (! $user->expositor?->is_active) {
            abort(403, 'Sua conta de lojista está inativa. Entre em contato com a administração.');
        }

        return $next($request);
    }
}

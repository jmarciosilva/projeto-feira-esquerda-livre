<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, CartService $cart): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Credenciais inválidas.',
            ])->onlyInput('email');
        }

        if (!Auth::user()->is_active) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Sua conta está desativada.',
            ])->onlyInput('email');
        }

        $oldSessionId = $request->session()->getId();
        $request->session()->regenerate();
        $cart->reassignSession($oldSessionId, Auth::id());
        $redirectTo = $this->safeRedirectPath($request);

        $default = match (Auth::user()->role) {
            UserRole::Admin, UserRole::Editor => route('admin.dashboard'),
            UserRole::Lojista                 => route('lojista.dashboard'),
            default                            => route('cliente.pedidos.index'),
        };

        // Admin e lojista sempre vão ao próprio painel, nunca para a URL intended
        if (in_array(Auth::user()->role, [UserRole::Admin, UserRole::Editor, UserRole::Lojista])) {
            return redirect($default);
        }

        return $redirectTo
            ? redirect($redirectTo)
            : redirect()->intended($default);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function safeRedirectPath(Request $request): ?string
    {
        $redirectTo = (string) $request->input('redirect_to', '');

        if ($redirectTo === '') {
            return null;
        }

        $parts = parse_url($redirectTo);

        if ($parts === false) {
            return null;
        }

        if (isset($parts['host']) && $parts['host'] !== $request->getHost()) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return str_starts_with($path, '/') ? $path . $query : null;
    }
}

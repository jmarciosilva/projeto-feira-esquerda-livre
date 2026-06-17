<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, CartService $cart): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'whatsapp'              => ['required', 'string', 'max:20'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'     => 'Informe seu nome.',
            'email.required'    => 'Informe seu e-mail.',
            'email.unique'      => 'Já existe uma conta com este e-mail. Tente entrar.',
            'whatsapp.required' => 'Informe seu WhatsApp.',
            'password.min'      => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed'=> 'A confirmação de senha não corresponde.',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'whatsapp'  => $validated['whatsapp'],
            'password'  => Hash::make($validated['password']),
            'role'      => UserRole::User,
            'is_active' => true,
        ]);

        $oldSessionId = $request->session()->getId();
        Auth::login($user);
        $request->session()->regenerate();
        $cart->reassignSession($oldSessionId, $user->id);

        return redirect()->intended(route('cliente.pedidos.index'));
    }
}

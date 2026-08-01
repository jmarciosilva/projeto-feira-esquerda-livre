<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Cadastro de cliente comprador. Lojistas são criados apenas via aprovação
     * de solicitação (/seja-um-expositor) e continuam fazendo login por aqui.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'whatsapp' => $data['whatsapp'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::User,
            'is_active' => true,
        ]);

        $token = $user->createToken($data['device_name'] ?? 'app')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('customerProfile')),
            'token' => $token,
        ], 201);
    }

    /**
     * Login único para cliente e lojista. Validação manual (sem Auth::attempt)
     * porque a rota de API não roda a sessão do guard "web".
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Sua conta está desativada.'],
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'app')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load(['customerProfile', 'expositor'])),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['customerProfile', 'expositor']);

        return response()->json(['user' => new UserResource($user)]);
    }
}

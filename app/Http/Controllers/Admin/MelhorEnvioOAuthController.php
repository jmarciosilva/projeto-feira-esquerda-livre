<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\Shipping\MelhorEnvioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MelhorEnvioOAuthController extends Controller
{
    private const SCOPES = [
        'shipping-calculate',
        'shipping-cancel',
        'shipping-checkout',
        'shipping-companies',
        'shipping-generate',
        'shipping-preview',
        'shipping-print',
        'shipping-share',
        'shipping-tracking',
        'ecommerce-shipping',
    ];

    /** Inicia o fluxo OAuth2 redirecionando para a tela de autorização do Melhor Envio. */
    public function connect(Request $request, MelhorEnvioService $shipping): RedirectResponse
    {
        $settings = SiteSetting::instance();

        if (blank($settings->melhor_envio_client_id) || blank($settings->melhor_envio_client_secret)) {
            return redirect()->route('admin.settings.checkout')
                ->with('melhor_envio_error', 'Informe e salve o Client ID e o Client Secret do Melhor Envio antes de conectar.');
        }

        $state = Str::random(40);
        $request->session()->put('melhor_envio_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $settings->melhor_envio_client_id,
            'redirect_uri' => route('admin.melhor-envio.callback'),
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'state' => $state,
        ]);

        return redirect()->away($shipping->baseUrl()."/oauth/authorize?{$query}");
    }

    /** Recebe o retorno do Melhor Envio, troca o code por tokens e salva nas configurações. */
    public function callback(Request $request, MelhorEnvioService $shipping): RedirectResponse
    {
        $expectedState = $request->session()->pull('melhor_envio_oauth_state');

        if ($request->query('error')) {
            return redirect()->route('admin.settings.checkout')
                ->with('melhor_envio_error', 'Autorização cancelada no Melhor Envio: '.$request->query('error_description', (string) $request->query('error')));
        }

        if (blank($expectedState) || ! hash_equals((string) $expectedState, (string) $request->query('state'))) {
            return redirect()->route('admin.settings.checkout')
                ->with('melhor_envio_error', 'Não foi possível validar o retorno do Melhor Envio. Tente conectar novamente.');
        }

        $code = $request->query('code');

        if (blank($code)) {
            return redirect()->route('admin.settings.checkout')
                ->with('melhor_envio_error', 'O Melhor Envio não retornou um código de autorização.');
        }

        $settings = SiteSetting::instance();

        try {
            $response = Http::baseUrl($shipping->baseUrl())
                ->asJson()
                ->acceptJson()
                ->post('/oauth/token', [
                    'grant_type' => 'authorization_code',
                    'client_id' => $settings->melhor_envio_client_id,
                    'client_secret' => $settings->melhor_envio_client_secret,
                    'redirect_uri' => route('admin.melhor-envio.callback'),
                    'code' => $code,
                ])
                ->throw();

            $data = $response->json();

            $settings->update([
                'melhor_envio_token' => $data['access_token'] ?? null,
                'melhor_envio_refresh_token' => $data['refresh_token'] ?? null,
                'melhor_envio_token_expires_at' => now()->addSeconds((int) ($data['expires_in'] ?? 2592000)),
                'melhor_envio_ativo' => true,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('admin.settings.checkout')
                ->with('melhor_envio_error', 'Não foi possível concluir a conexão com o Melhor Envio. Verifique as credenciais e tente novamente.');
        }

        return redirect()->route('admin.settings.checkout')
            ->with('melhor_envio_success', 'Melhor Envio conectado com sucesso!');
    }
}

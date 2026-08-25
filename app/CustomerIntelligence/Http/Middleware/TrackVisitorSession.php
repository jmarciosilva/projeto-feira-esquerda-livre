<?php

namespace App\CustomerIntelligence\Http\Middleware;

use App\CustomerIntelligence\Actions\ResolveVisitorSession;
use App\CustomerIntelligence\Support\VisitorContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve o visitante e a sessao da requisicao atual e os grava em
 * `ci_visitors` / `ci_sessions`.
 *
 * ── Convivencia com o middleware do SDK externo ─────────────────────────────
 *
 * Enquanto a migracao nao termina, o middleware `ResolveVisitorAndSession` do
 * SDK continua ativo no mesmo grupo `web` e emite os MESMOS cookies. Duas
 * medidas evitam que os dois briguem:
 *
 * 1. Ordem deterministica. Este middleware e anexado ao grupo pelo `boot()` do
 *    CustomerIntelligenceServiceProvider, que roda depois do provider do SDK
 *    (providers da aplicacao inicializam depois dos descobertos por pacote).
 *    Como cada um faz `appendMiddlewareToGroup`, este fica por ultimo na
 *    pilha e enxerga o que o SDK ja decidiu.
 *
 * 2. Adocao do valor ja enfileirado. Numa primeira visita nao existe cookie na
 *    requisicao, e o SDK acabou de gerar um identificador proprio. Em vez de
 *    gerar outro — o que faria o servidor remoto e o banco local conhecerem o
 *    mesmo visitante por dois nomes —, lemos o valor que ele enfileirou.
 *
 * Reenfileirar o cookie com o mesmo nome e seguro: o CookieJar do Laravel
 * indexa a fila por nome e caminho, entao a segunda chamada substitui a
 * primeira e apenas um `Set-Cookie` sai na resposta.
 *
 * Quando o SDK for removido (CI-07), nada aqui precisa mudar: sem ninguem
 * para adotar, este middleware passa a gerar os proprios identificadores.
 */
class TrackVisitorSession
{
    public function __construct(
        private readonly VisitorContext $context,
        private readonly ResolveVisitorSession $resolve,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('customer-intelligence-internal.enabled', true)) {
            return $next($request);
        }

        $visitorCookie = (array) config('customer-intelligence-internal.visitor_cookie');
        $sessionCookie = (array) config('customer-intelligence-internal.session_cookie');

        $visitorUuid = $this->currentValue($request, $visitorCookie['name']) ?? $this->newIdentifier();
        $sessionUuid = $this->currentValue($request, $sessionCookie['name']) ?? $this->newIdentifier();

        $session = ($this->resolve)(
            $visitorUuid,
            $sessionUuid,
            Auth::id(),
            $this->origin($request),
        );

        $this->context->setSession($session);

        // Os valores vem do que foi efetivamente gravado: se a sessao expirou,
        // `startSession()` abriu outra e o cookie precisa acompanhar.
        Cookie::queue($visitorCookie['name'], $session->visitor->visitor_uuid, $visitorCookie['minutes']);
        Cookie::queue($sessionCookie['name'], $session->session_uuid, $sessionCookie['minutes']);

        return $next($request);
    }

    /**
     * Valor vigente do cookie: o que o navegador enviou ou, na primeira visita,
     * o que outro middleware ja enfileirou para esta resposta.
     */
    private function currentValue(Request $request, string $name): ?string
    {
        $fromRequest = $request->cookie($name);

        if (is_string($fromRequest) && $fromRequest !== '') {
            return $fromRequest;
        }

        $queued = Cookie::queued($name);

        return $queued === null || $queued->getValue() === '' ? null : $queued->getValue();
    }

    private function newIdentifier(): string
    {
        return (string) Str::orderedUuid();
    }

    /**
     * Origem da sessao, gravada apenas quando ela e aberta.
     *
     * Guardamos o caminho, e nao a URL completa: uma query string pode carregar
     * dado pessoal, e o modulo trabalha com o minimo necessario. Pelo mesmo
     * motivo o referrer e reduzido a esquema, host e caminho.
     *
     * @return array<string, string|null>
     */
    private function origin(Request $request): array
    {
        return [
            'landing_url' => Str::limit('/'.ltrim($request->path(), '/'), 500, ''),
            'referrer' => $this->cleanReferrer($request->headers->get('referer')),
            'utm_source' => $this->utm($request, 'utm_source'),
            'utm_medium' => $this->utm($request, 'utm_medium'),
            'utm_campaign' => $this->utm($request, 'utm_campaign'),
        ];
    }

    private function cleanReferrer(?string $referrer): ?string
    {
        if (! is_string($referrer) || $referrer === '') {
            return null;
        }

        $parts = parse_url($referrer);

        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';

        return Str::limit($scheme.$parts['host'].($parts['path'] ?? ''), 500, '');
    }

    private function utm(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && $value !== '' ? Str::limit($value, 120, '') : null;
    }
}

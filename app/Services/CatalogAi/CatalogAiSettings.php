<?php

namespace App\Services\CatalogAi;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;

/**
 * A configuração do provider externo do Catalog Intelligence, como o painel a grava — CAT-10A.1.
 *
 * O banco é a única autoridade operacional: ligado, provider, modelo, chave e prazo vêm
 * de `site_settings` (linha 1), gravados pela tela Admin → Configurações → Inteligência
 * Artificial. O `.env` não fornece nenhum desses valores.
 *
 * Mora fora de `app/CatalogIntelligence`: o domínio continua sem banco de configuração,
 * Livewire, fornecedor ou credencial.
 *
 * ## Leitura
 *
 * - `SiteSetting::query()->find(1)`, e não `SiteSetting::instance()`: ler nunca cria a
 *   linha. Sem linha, não há configuração.
 * - Sem cache e sem `config()`: cada resolução lê o que está gravado, e uma mudança no
 *   painel vale na requisição seguinte, sem limpar cache nem reiniciar nada.
 * - Sem `try` e sem log. A chave só é decriptada com o provider ligado; se não se
 *   decripta — `APP_KEY` trocada —, a `DecryptException` sobe: é defeito de
 *   infraestrutura, e não "sem provider".
 *
 * Quem valida o que foi lido é o `CatalogAiProviderSelector`.
 *
 * ## Escrita
 *
 * Pela `SiteSettingService`. Chave em branco mantém a gravada; remover a chave também
 * desliga o provider.
 *
 * Não é `final` de propósito: os testes do seletor a substituem para exercitar valores
 * que a coluna tipada não guarda.
 */
class CatalogAiSettings
{
    public function __construct(
        private readonly SiteSettingService $gravacao = new SiteSettingService,
    ) {}

    /**
     * O que o seletor valida. Vazio quando nada foi gravado.
     *
     * @return array<string, mixed>
     */
    public function atual(): array
    {
        $settings = SiteSetting::query()->find(1);

        if ($settings === null) {
            return [];
        }

        return [
            'enabled' => $settings->catalog_ai_ativo,
            'provider' => $settings->catalog_ai_provider,
            'model' => $settings->catalog_ai_modelo,
            // Decripta só o que pode ser usado: desligado, a chave nem é lida.
            'api_key' => $settings->catalog_ai_ativo ? $settings->catalog_ai_api_key : null,
            'timeout' => $settings->catalog_ai_timeout,
        ];
    }

    /**
     * O que a tela mostra: nunca a chave, só se ela existe.
     *
     * @return array{ativo: bool, provider: ?string, modelo: ?string, timeout: ?int, chave_configurada: bool}
     */
    public function paraOPainel(): array
    {
        $settings = SiteSetting::query()->find(1);

        return [
            'ativo' => (bool) $settings?->catalog_ai_ativo,
            'provider' => $settings?->catalog_ai_provider,
            'modelo' => $settings?->catalog_ai_modelo,
            'timeout' => $settings?->catalog_ai_timeout,
            'chave_configurada' => $settings?->segredoConfigurado('catalog_ai_api_key') ?? false,
        ];
    }

    public function chaveConfigurada(): bool
    {
        return SiteSetting::query()->find(1)?->segredoConfigurado('catalog_ai_api_key') ?? false;
    }

    /** Grava a configuração. `$novaChave` nula ou em branco mantém a chave gravada. */
    public function salvar(
        bool $ativo,
        string $provider,
        ?string $modelo,
        ?int $timeout,
        #[\SensitiveParameter] ?string $novaChave,
    ): void {
        $dados = [
            'catalog_ai_ativo' => $ativo,
            'catalog_ai_provider' => $provider,
            'catalog_ai_modelo' => $modelo !== null && trim($modelo) !== '' ? trim($modelo) : null,
            'catalog_ai_timeout' => $timeout,
        ];

        if ($novaChave !== null && trim($novaChave) !== '') {
            $dados['catalog_ai_api_key'] = trim($novaChave);
        }

        $this->gravacao->save($dados);
    }

    /** Apaga a chave e desliga o provider. */
    public function removerChave(): void
    {
        $this->gravacao->save([
            'catalog_ai_api_key' => null,
            'catalog_ai_ativo' => false,
        ]);
    }
}

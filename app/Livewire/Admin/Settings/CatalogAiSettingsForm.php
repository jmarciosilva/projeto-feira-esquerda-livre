<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Services\CatalogAi\CatalogAiSettings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidadorDeDados;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Admin → Configurações → Inteligência Artificial — CAT-10A.1.
 *
 * Liga, desliga e configura o provider externo do Catalog Intelligence sem SSH, `.env`
 * nem cache: o `CatalogAiProviderSelector` lê o banco a cada resolução, e a mudança vale
 * na geração de sugestão seguinte.
 *
 * ## A chave nunca vai ao navegador
 *
 * Toda propriedade pública viaja no snapshot do Livewire. Por isso:
 *
 * - a chave gravada nunca é carregada; a tela recebe só `$chaveConfigurada`;
 * - `$novaChave` só existe enquanto o administrador digita: cada ação a copia para uma
 *   variável local e a esvazia **antes** de autorizar, validar ou gravar, e nenhuma
 *   resposta — nem a de erro de validação — a devolve.
 *
 * Tudo exige `configuracoes.editar`, inclusive ver a tela.
 */
class CatalogAiSettingsForm extends Component
{
    use AuthorizesAdminActions;

    private const PERMISSAO = 'configuracoes.editar';

    public bool $ativo = false;

    public string $provider = 'openai';

    public string $modelo = '';

    public string $timeout = '';

    /** Só a chave NOVA, enquanto é digitada. Nunca a gravada. */
    public string $novaChave = '';

    #[Locked]
    public bool $chaveConfigurada = false;

    public bool $saved = false;

    public function mount(CatalogAiSettings $settings): void
    {
        $this->authorizeAdminAction(self::PERMISSAO);

        $this->carregar($settings);
    }

    public function save(CatalogAiSettings $settings): void
    {
        $novaChave = trim($this->novaChave);
        $this->novaChave = '';
        $this->saved = false;

        $this->authorizeAdminAction(self::PERMISSAO);

        $modelo = trim($this->modelo);
        $timeout = trim($this->timeout);

        $validador = Validator::make([
            'ativo' => $this->ativo,
            'provider' => $this->provider,
            'modelo' => $modelo,
            'timeout' => $timeout,
            'novaChave' => $novaChave,
        ], [
            'ativo' => ['boolean'],
            'provider' => ['required', Rule::in(['openai'])],
            'modelo' => [Rule::requiredIf($this->ativo), 'nullable', 'string', 'max:100', 'regex:/^[^\s\x00-\x1F\x7F]+$/u'],
            'timeout' => ['nullable', 'integer', 'between:1,8'],
            'novaChave' => ['nullable', 'string', 'max:500', 'regex:/^\S+$/u'],
        ], [
            'ativo.boolean' => 'Valor inválido para ativar o provider.',
            'provider.required' => 'Escolha o provider.',
            'provider.in' => 'Provider não suportado.',
            'modelo.required' => 'Informe o modelo para ativar o provider externo.',
            'modelo.string' => 'Modelo inválido.',
            'modelo.max' => 'O modelo pode ter no máximo 100 caracteres.',
            'modelo.regex' => 'O modelo não pode ter espaços.',
            'timeout.integer' => 'O timeout deve ser um número inteiro entre 1 e 8 segundos.',
            'timeout.between' => 'O timeout deve ser um número inteiro entre 1 e 8 segundos.',
            'novaChave.string' => 'API key inválida.',
            'novaChave.max' => 'A API key pode ter no máximo 500 caracteres.',
            'novaChave.regex' => 'A API key não pode ter espaços.',
        ]);

        $validador->after(function (ValidadorDeDados $validacao) use ($settings, $novaChave) {
            if ($this->ativo && $novaChave === '' && ! $settings->chaveConfigurada()) {
                $validacao->errors()->add('novaChave', 'Informe a API key para ativar o provider externo.');
            }
        });

        $validador->validate();

        $settings->salvar(
            ativo: $this->ativo,
            provider: $this->provider,
            modelo: $modelo !== '' ? $modelo : null,
            timeout: $timeout !== '' ? (int) $timeout : null,
            novaChave: $novaChave !== '' ? $novaChave : null,
        );

        $this->carregar($settings);
        $this->saved = true;
    }

    public function removerChave(CatalogAiSettings $settings): void
    {
        $this->novaChave = '';
        $this->saved = false;

        $this->authorizeAdminAction(self::PERMISSAO);

        $settings->removerChave();

        $this->resetErrorBag();
        $this->carregar($settings);
        $this->saved = true;
    }

    private function carregar(CatalogAiSettings $settings): void
    {
        $painel = $settings->paraOPainel();

        $this->ativo = $painel['ativo'];
        $this->provider = $painel['provider'] ?? 'openai';
        $this->modelo = $painel['modelo'] ?? '';
        $this->timeout = $painel['timeout'] !== null ? (string) $painel['timeout'] : '';
        $this->chaveConfigurada = $painel['chave_configurada'];
    }

    public function render(): View
    {
        return view('livewire.admin.settings.catalog-ai-settings-form')
            ->layout('admin.layouts.app', ['title' => 'Inteligência Artificial']);
    }
}

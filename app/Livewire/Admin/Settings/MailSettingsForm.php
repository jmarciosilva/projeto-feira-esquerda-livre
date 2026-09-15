<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;

class MailSettingsForm extends Component
{
    use AuthorizesAdminActions;

    public string  $mail_mailer       = 'smtp';
    public string  $mail_host         = '';
    public string  $mail_port         = '587';
    public string  $mail_username     = '';

    /**
     * Só a senha NOVA, enquanto é digitada — CAT-10A.1.
     *
     * A senha gravada nunca é carregada aqui: toda propriedade pública vai no snapshot
     * do Livewire, e a tela recebe só `$mail_password_configurada`. Cada ação esvazia
     * este campo antes de autorizar, validar ou gravar. Em branco, a senha gravada é
     * mantida; remover é a ação `removerSenha`.
     */
    public string  $mail_password     = '';

    #[Locked]
    public bool    $mail_password_configurada = false;

    public string  $mail_encryption   = 'tls';
    public string  $mail_from_address = '';
    public string  $mail_from_name    = '';

    public string  $test_email        = '';
    public bool    $saved             = false;
    public ?string $test_result       = null;
    public bool    $test_ok           = false;

    public function mount(): void
    {
        $s = SiteSetting::instance();

        $this->mail_mailer       = $s->mail_mailer       ?? 'smtp';
        $this->mail_host         = $s->mail_host         ?? '';
        $this->mail_port         = (string) ($s->mail_port ?? '587');
        $this->mail_username     = $s->mail_username     ?? '';
        $this->mail_password_configurada = $s->segredoConfigurado('mail_password');
        $this->mail_encryption   = $s->mail_encryption   ?? 'tls';
        $this->mail_from_address = $s->mail_from_address ?? '';
        $this->mail_from_name    = $s->mail_from_name    ?? '';
    }

    public function save(SiteSettingService $service): void
    {
        $novaSenha = $this->mail_password;
        $this->mail_password = '';

        $this->authorizeAdminAction('configuracoes.editar');

        Validator::make([
            'mail_mailer'       => $this->mail_mailer,
            'mail_host'         => $this->mail_host,
            'mail_port'         => $this->mail_port,
            'mail_username'     => $this->mail_username,
            'mail_password'     => $novaSenha,
            'mail_encryption'   => $this->mail_encryption,
            'mail_from_address' => $this->mail_from_address,
            'mail_from_name'    => $this->mail_from_name,
        ], [
            'mail_mailer'       => 'required|in:smtp,sendmail,log',
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer|min:1|max:65535',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:500',
            'mail_encryption'   => 'nullable|in:tls,ssl,',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name'    => 'nullable|string|max:100',
        ])->validate();

        $service->save([
            'mail_mailer'       => $this->mail_mailer,
            'mail_host'         => $this->mail_host         ?: null,
            'mail_port'         => $this->mail_port         ? (int) $this->mail_port : null,
            'mail_username'     => $this->mail_username     ?: null,
            // Em branco, a senha gravada é mantida; só a digitada a substitui.
            ...(filled($novaSenha) ? ['mail_password' => $novaSenha] : []),
            'mail_encryption'   => $this->mail_encryption   ?: null,
            'mail_from_address' => $this->mail_from_address ?: null,
            'mail_from_name'    => $this->mail_from_name    ?: null,
        ]);

        $this->mail_password_configurada = SiteSetting::instance()->segredoConfigurado('mail_password');
        $this->saved       = true;
        $this->test_result = null;
    }

    public function removerSenha(SiteSettingService $service): void
    {
        $this->mail_password = '';

        $this->authorizeAdminAction('configuracoes.editar');

        $service->save(['mail_password' => null]);

        $this->mail_password_configurada = false;
        $this->saved       = true;
        $this->test_result = null;
    }

    public function sendTest(): void
    {
        $this->authorizeAdminAction('configuracoes.editar');

        $this->validate([
            'test_email' => 'required|email',
        ], [], ['test_email' => 'e-mail de teste']);

        $to = $this->test_email;

        try {
            Mail::raw(
                "Olá!\n\nEste é um e-mail de teste enviado pelo painel administrativo da Feira Esquerda Livre.\n\nSe você recebeu esta mensagem, a configuração de e-mail está funcionando corretamente.\n\nFeira Esquerda Livre",
                fn ($m) => $m->to($to)->subject('Teste de E-mail — Feira Esquerda Livre')
            );

            $this->test_result = 'E-mail enviado com sucesso para ' . $to . '.';
            $this->test_ok     = true;
        } catch (\Throwable) {
            // A mensagem do transporte pode trazer host, usuário e trechos da conversa SMTP — não vai ao navegador.
            $this->test_result = 'Não foi possível enviar o e-mail de teste. Verifique as configurações e tente novamente.';
            $this->test_ok     = false;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.settings.mail-settings-form')
            ->layout('admin.layouts.app', ['title' => 'Configurações de E-mail']);
    }
}

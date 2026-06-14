<?php

namespace App\Livewire\Admin\Settings;

use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class MailSettingsForm extends Component
{
    public string  $mail_mailer       = 'smtp';
    public string  $mail_host         = '';
    public string  $mail_port         = '587';
    public string  $mail_username     = '';
    public string  $mail_password     = '';
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
        $this->mail_password     = $s->mail_password     ?? '';
        $this->mail_encryption   = $s->mail_encryption   ?? 'tls';
        $this->mail_from_address = $s->mail_from_address ?? '';
        $this->mail_from_name    = $s->mail_from_name    ?? '';
    }

    public function save(SiteSettingService $service): void
    {
        $this->validate([
            'mail_mailer'       => 'required|in:smtp,sendmail,log',
            'mail_host'         => 'nullable|string|max:255',
            'mail_port'         => 'nullable|integer|min:1|max:65535',
            'mail_username'     => 'nullable|string|max:255',
            'mail_password'     => 'nullable|string|max:500',
            'mail_encryption'   => 'nullable|in:tls,ssl,',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name'    => 'nullable|string|max:100',
        ]);

        $service->save([
            'mail_mailer'       => $this->mail_mailer,
            'mail_host'         => $this->mail_host         ?: null,
            'mail_port'         => $this->mail_port         ? (int) $this->mail_port : null,
            'mail_username'     => $this->mail_username     ?: null,
            'mail_password'     => $this->mail_password     ?: null,
            'mail_encryption'   => $this->mail_encryption   ?: null,
            'mail_from_address' => $this->mail_from_address ?: null,
            'mail_from_name'    => $this->mail_from_name    ?: null,
        ]);

        $this->saved      = true;
        $this->test_result = null;
    }

    public function sendTest(): void
    {
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
        } catch (\Throwable $e) {
            $this->test_result = 'Falha ao enviar: ' . $e->getMessage();
            $this->test_ok     = false;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.settings.mail-settings-form')
            ->layout('admin.layouts.app', ['title' => 'Configurações de E-mail']);
    }
}

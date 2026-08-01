<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsForm extends Component
{
    use AuthorizesAdminActions, WithFileUploads, ValidatesFileUploads;

    public string $site_name        = '';
    public string $site_description = '';
    public string $whatsapp         = '';
    public string $email            = '';
    public string $instagram_url    = '';
    public string $facebook_url     = '';
    public string $youtube_url      = '';
    public string $address          = '';
    public string $footer_text      = '';
    public bool   $maintenance_mode = false;

    public string  $sobre_titulo       = '';
    public string  $sobre_texto        = '';

    public string $color_primary         = '#E8A000';
    public string $color_primary_dark    = '#C47A00';
    public string $color_secondary       = '#F4E294';
    public string $color_secondary_light = '#FDF8DC';
    public string $color_dark            = '#3D3000';

    public string $contrato_expositor = '';

    public $logo_upload         = null;
    public $favicon_upload      = null;
    public $sobre_imagem_upload = null;

    public ?string $logo_path         = null;
    public ?string $favicon_path      = null;
    public ?string $sobre_imagem_path = null;

    public bool $saved = false;

    public function mount(): void
    {
        $setting = SiteSetting::instance();

        $this->site_name        = $setting->site_name ?? '';
        $this->site_description = $setting->site_description ?? '';
        $this->whatsapp         = $setting->whatsapp ?? '';
        $this->email            = $setting->email ?? '';
        $this->instagram_url    = $setting->instagram_url ?? '';
        $this->facebook_url     = $setting->facebook_url ?? '';
        $this->youtube_url      = $setting->youtube_url ?? '';
        $this->address          = $setting->address ?? '';
        $this->footer_text      = $setting->footer_text ?? '';
        $this->maintenance_mode   = (bool) $setting->maintenance_mode;
        $this->sobre_titulo          = $setting->sobre_titulo ?? '';
        $this->sobre_texto           = $setting->sobre_texto ?? '';
        $this->color_primary         = $setting->color_primary         ?? '#E8A000';
        $this->color_primary_dark    = $setting->color_primary_dark    ?? '#C47A00';
        $this->color_secondary       = $setting->color_secondary       ?? '#F4E294';
        $this->color_secondary_light = $setting->color_secondary_light ?? '#FDF8DC';
        $this->color_dark            = $setting->color_dark            ?? '#3D3000';
        $this->contrato_expositor    = $setting->contrato_expositor    ?? '';
        $this->logo_path          = $setting->logo_path;
        $this->favicon_path       = $setting->favicon_path;
        $this->sobre_imagem_path  = $setting->sobre_imagem_path;
    }

    public function save(SiteSettingService $service): void
    {
        $this->authorizeAdminAction('configuracoes.editar');

        if (! $this->checkUploadedFile($this->logo_upload, 2048, 'logo_upload')) return;
        if (! $this->checkUploadedFile($this->favicon_upload, 512, 'favicon_upload')) return;
        if (! $this->checkUploadedFile($this->sobre_imagem_upload, 4096, 'sobre_imagem_upload')) return;

        $this->validate([
            'site_name'           => 'required|string|max:100',
            'email'               => 'nullable|email|max:100',
            'whatsapp'            => 'nullable|string|max:20',
            'logo_upload'         => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg',
            'favicon_upload'      => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,ico',
            'sobre_imagem_upload' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg',
        ]);

        $data = [
            'site_name'        => $this->site_name,
            'site_description' => $this->site_description,
            'whatsapp'         => $this->whatsapp,
            'email'            => $this->email,
            'instagram_url'    => $this->instagram_url,
            'facebook_url'     => $this->facebook_url,
            'youtube_url'      => $this->youtube_url,
            'address'          => $this->address,
            'footer_text'      => $this->footer_text,
            'maintenance_mode' => $this->maintenance_mode,
            'sobre_titulo'          => $this->sobre_titulo,
            'sobre_texto'           => $this->sobre_texto,
            'color_primary'         => $this->color_primary,
            'color_primary_dark'    => $this->color_primary_dark,
            'color_secondary'       => $this->color_secondary,
            'color_secondary_light' => $this->color_secondary_light,
            'color_dark'            => $this->color_dark,
            'contrato_expositor'    => $this->contrato_expositor,
        ];

        if ($this->logo_upload) {
            if ($this->logo_path) {
                Storage::disk('public')->delete($this->logo_path);
            }
            $data['logo_path'] = $this->logo_upload->store('site', 'public');
        }

        if ($this->favicon_upload) {
            if ($this->favicon_path) {
                Storage::disk('public')->delete($this->favicon_path);
            }
            $data['favicon_path'] = $this->favicon_upload->store('site', 'public');
        }

        if ($this->sobre_imagem_upload) {
            if ($this->sobre_imagem_path) {
                Storage::disk('public')->delete($this->sobre_imagem_path);
            }
            $data['sobre_imagem_path'] = $this->sobre_imagem_upload->store('site', 'public');
            $this->sobre_imagem_path   = $data['sobre_imagem_path'];
        }

        $setting = $service->save($data);

        $this->logo_path         = $setting->logo_path;
        $this->favicon_path      = $setting->favicon_path;
        $this->sobre_imagem_path = $setting->sobre_imagem_path;
        $this->logo_upload = null;
        $this->favicon_upload = null;
        $this->sobre_imagem_upload = null;

        $this->saved = true;
        $this->dispatch('saved');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.settings.settings-form')
            ->layout('admin.layouts.app', ['title' => 'Configurações do Site']);
    }
}

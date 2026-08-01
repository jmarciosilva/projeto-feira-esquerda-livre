<?php

namespace App\Livewire\Admin\Banners;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\Banner;
use App\Services\BannerService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class BannerForm extends Component
{
    use AuthorizesAdminActions, WithFileUploads, ValidatesFileUploads;

    public ?Banner $banner = null;

    public string  $title              = '';
    public string  $subtitle           = '';
    public string  $button_text        = '';
    public string  $button_link        = '';
    public string  $button_link_preset = '';
    public int     $sort_order         = 0;
    public string  $start_date         = '';
    public string  $end_date           = '';
    public bool    $is_active          = true;
    public ?string $image_path         = null;
    public ?string $mobile_image_path  = null;
    public $image_upload               = null;
    public $mobile_image_upload        = null;

    public function mount(?Banner $banner = null): void
    {
        if ($banner && $banner->exists) {
            $this->banner             = $banner;
            $this->title              = $banner->title;
            $this->subtitle           = $banner->subtitle ?? '';
            $this->button_text        = $banner->button_text ?? '';
            $this->button_link        = $banner->button_link ?? '';
            $this->button_link_preset = $this->detectButtonLinkPreset($this->button_link);
            $this->sort_order         = $banner->sort_order;
            $this->start_date         = $banner->start_date?->format('Y-m-d') ?? '';
            $this->end_date           = $banner->end_date?->format('Y-m-d') ?? '';
            $this->is_active          = $banner->is_active;
            $this->image_path         = $banner->image_path;
            $this->mobile_image_path  = $banner->mobile_image_path;
        }
    }

    public function save(BannerService $service): void
    {
        $this->authorizeAdminAction('cms.editar');

        if (! $this->checkUploadedFile($this->image_upload, 4096, 'image_upload')) return;
        if (! $this->checkUploadedFile($this->mobile_image_upload, 4096, 'mobile_image_upload')) return;

        $this->validate([
            'title'               => 'required|string|max:255',
            'image_upload'        => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg',
            'mobile_image_upload' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg',
            'button_link'         => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/|#)/'],
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
        ], [
            'button_link.regex' => 'Escolha uma opção da lista ou informe uma URL começando com https://, / ou #.',
        ]);

        $data = [
            'title'       => $this->title,
            'subtitle'    => $this->subtitle,
            'button_text' => $this->button_text,
            'button_link' => $this->button_link,
            'sort_order'  => $this->sort_order,
            'start_date'  => $this->start_date ?: null,
            'end_date'    => $this->end_date ?: null,
            'is_active'   => $this->is_active,
        ];

        if ($this->image_upload) {
            if ($this->image_path) {
                Storage::disk('public')->delete($this->image_path);
            }
            $data['image_path'] = $this->image_upload->store('banners', 'public');
        }

        if ($this->mobile_image_upload) {
            if ($this->mobile_image_path) {
                Storage::disk('public')->delete($this->mobile_image_path);
            }
            $data['mobile_image_path'] = $this->mobile_image_upload->store('banners', 'public');
        }

        if ($this->banner && $this->banner->exists) {
            $service->update($this->banner, $data);
            session()->flash('success', 'Banner atualizado.');
        } else {
            if (!isset($data['image_path'])) {
                $this->addError('image_upload', 'A imagem é obrigatória.');
                return;
            }
            $service->create($data);
            session()->flash('success', 'Banner criado com sucesso.');
            $this->redirect(route('admin.banners.index'));
        }
    }

    public function updatedButtonLinkPreset(string $value): void
    {
        if ($value !== 'custom') {
            $this->button_link = $value;
        }
    }

    public function updatedButtonLink(string $value): void
    {
        $this->button_link_preset = $this->detectButtonLinkPreset($value);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function buttonLinkOptions(): array
    {
        return [
            ['label' => 'Home', 'value' => '/'],
            ['label' => 'Agenda de feiras', 'value' => '/agenda'],
            ['label' => 'Produtos', 'value' => '/produtos'],
            ['label' => 'Serviços', 'value' => '/servicos'],
            ['label' => 'Cuidados & Bem Viver', 'value' => '/cuidados'],
            ['label' => 'Comunidade', 'value' => '/feed'],
            ['label' => 'Seja um Expositor', 'value' => '/seja-um-expositor'],
            ['label' => 'Marketplace na home', 'value' => '/#marketplace'],
            ['label' => 'Próximas feiras na home', 'value' => '/#agenda'],
            ['label' => 'Expositores na home', 'value' => '/#expositores'],
            ['label' => 'Quem Somos / Sobre nós', 'value' => '/#sobre'],
            ['label' => 'Notícias na home', 'value' => '/#noticias'],
            ['label' => 'Contato', 'value' => '/contato'],
        ];
    }

    private function detectButtonLinkPreset(?string $link): string
    {
        $link = trim((string) $link);

        if ($link === '') {
            return '';
        }

        $values = array_column($this->buttonLinkOptions(), 'value');

        return in_array($link, $values, true) ? $link : 'custom';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.banners.banner-form', [
            'buttonLinkOptions' => $this->buttonLinkOptions(),
        ])
            ->layout('admin.layouts.app', ['title' => $this->banner ? 'Editar Banner' : 'Novo Banner']);
    }
}

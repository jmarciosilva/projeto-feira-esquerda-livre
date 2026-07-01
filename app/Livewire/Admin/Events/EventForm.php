<?php

namespace App\Livewire\Admin\Events;

use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class EventForm extends Component
{
    use AuthorizesAdminActions, WithFileUploads, ValidatesFileUploads;

    public ?Event $event = null;

    public string  $title                  = '';
    public string  $slug                   = '';
    public string  $description            = '';
    public string  $state                  = '';
    public string  $city                   = '';
    public string  $address                = '';
    public string  $latitude               = '';
    public string  $longitude              = '';
    public string  $start_date             = '';
    public string  $end_date               = '';
    public string  $registration_url       = '';
    public bool    $is_active              = true;
    public bool    $is_featured            = false;
    public ?string $image_path             = null;
    public ?string $banner_image_path      = null;
    public ?int    $capacidade_expositores = null;
    public ?int    $vagas_restantes        = null;
    public $image_upload                   = null;
    public $banner_image_upload            = null;

    public array $brazilStates = [
        'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA',
        'MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN',
        'RS','RO','RR','SC','SP','SE','TO',
    ];

    public function mount(?Event $event = null): void
    {
        if ($event && $event->exists) {
            $this->event                  = $event;
            $this->title                  = $event->title;
            $this->slug                   = $event->slug;
            $this->description            = $event->description ?? '';
            $this->state                  = $event->state ?? '';
            $this->city                   = $event->city ?? '';
            $this->address                = $event->address ?? '';
            $this->latitude               = (string) ($event->latitude ?? '');
            $this->longitude              = (string) ($event->longitude ?? '');
            $this->start_date             = $event->start_date?->format('Y-m-d\TH:i') ?? '';
            $this->end_date               = $event->end_date?->format('Y-m-d\TH:i') ?? '';
            $this->registration_url       = $event->registration_url ?? '';
            $this->is_active              = $event->is_active;
            $this->is_featured            = $event->is_featured;
            $this->image_path             = $event->image_path;
            $this->banner_image_path      = $event->banner_image_path;
            $this->capacidade_expositores = $event->capacidade_expositores;
            $this->vagas_restantes        = $event->vagas_restantes;
        }
    }

    public function updatedTitle(): void
    {
        if (!$this->event) {
            $this->slug = \Illuminate\Support\Str::slug($this->title);
        }
    }

    public function save(EventService $service): void
    {
        $this->authorizeAdminAction('cms.editar');

        if (! $this->checkUploadedFile($this->image_upload, 4096, 'image_upload')) return;
        if (! $this->checkUploadedFile($this->banner_image_upload, 4096, 'banner_image_upload')) return;

        $this->validate([
            'title'                  => 'required|string|max:255',
            'start_date'             => 'required|date',
            'end_date'               => 'nullable|date|after_or_equal:start_date',
            'state'                  => 'nullable|string|max:2',
            'registration_url'       => 'nullable|url|max:500',
            'image_upload'           => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg',
            'banner_image_upload'    => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg',
            'latitude'               => 'nullable|numeric|between:-90,90',
            'longitude'              => 'nullable|numeric|between:-180,180',
            'capacidade_expositores' => 'nullable|integer|min:0',
            'vagas_restantes'        => 'nullable|integer|min:0',
        ]);

        $data = [
            'title'                  => $this->title,
            'slug'                   => $this->slug ?: $this->title,
            'description'            => $this->description,
            'state'                  => strtoupper($this->state),
            'city'                   => $this->city,
            'address'                => $this->address,
            'latitude'               => $this->latitude ?: null,
            'longitude'              => $this->longitude ?: null,
            'start_date'             => $this->start_date,
            'end_date'               => $this->end_date ?: null,
            'registration_url'       => $this->registration_url,
            'is_active'              => $this->is_active,
            'is_featured'            => $this->is_featured,
            'capacidade_expositores' => $this->capacidade_expositores ?: null,
            'vagas_restantes'        => $this->vagas_restantes ?: null,
        ];

        if ($this->image_upload) {
            if ($this->image_path) {
                Storage::disk('public')->delete($this->image_path);
            }
            $data['image_path'] = $this->image_upload->store('events', 'public');
        }

        if ($this->banner_image_upload) {
            if ($this->banner_image_path) {
                Storage::disk('public')->delete($this->banner_image_path);
            }
            $data['banner_image_path'] = $this->banner_image_upload->store('events', 'public');
        }

        if ($this->event && $this->event->exists) {
            $service->update($this->event, $data);
            session()->flash('success', 'Evento atualizado.');
        } else {
            $event = $service->create($data);
            session()->flash('success', 'Evento criado.');
            $this->redirect(route('admin.events.edit', $event));
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.events.event-form')
            ->layout('admin.layouts.app', ['title' => $this->event ? 'Editar Evento' : 'Novo Evento']);
    }
}

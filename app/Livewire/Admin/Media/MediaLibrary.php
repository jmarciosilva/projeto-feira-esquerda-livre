<?php

namespace App\Livewire\Admin\Media;

use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\Media;
use App\Services\MediaService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MediaLibrary extends Component
{
    use WithPagination, WithFileUploads, ValidatesFileUploads;

    public string $search        = '';
    public string $filterType    = '';
    public bool   $confirmDelete = false;
    public ?int   $deleteId      = null;
    public $uploads              = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function uploadFiles(MediaService $service): void
    {
        foreach ($this->uploads as $i => $file) {
            if (! $this->checkUploadedFile($file, 10240, "uploads.{$i}")) return;
        }

        $this->validate([
            'uploads.*' => 'file|mimes:jpg,jpeg,png,gif,webp,svg,pdf,mp4',
        ]);

        $count = count($this->uploads);
        foreach ($this->uploads as $file) {
            $service->upload($file, auth()->user());
        }

        $this->uploads = [];
        session()->flash('success', $count . ' arquivo(s) enviado(s).');
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteMedia(MediaService $service): void
    {
        $media = Media::findOrFail($this->deleteId);
        $service->delete($media);

        $this->confirmDelete = false;
        $this->deleteId      = null;
    }

    public function render(): \Illuminate\View\View
    {
        $media = Media::with('uploader')
            ->when($this->search, fn ($q) => $q->where('file_name', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn ($q) => $q->where('file_type', 'like', "{$this->filterType}%"))
            ->latest()
            ->paginate(24);

        return view('livewire.admin.media.media-library', compact('media'))
            ->layout('admin.layouts.app', ['title' => 'Gerenciador de Mídias']);
    }
}

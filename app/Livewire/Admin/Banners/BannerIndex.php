<?php

namespace App\Livewire\Admin\Banners;

use App\Models\Banner;
use App\Services\BannerService;
use Livewire\Component;
use Livewire\WithPagination;

class BannerIndex extends Component
{
    use WithPagination;

    public string $search        = '';
    public bool   $confirmDelete = false;
    public ?int   $deleteId      = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deleteBanner(BannerService $service): void
    {
        $banner = Banner::findOrFail($this->deleteId);
        $service->delete($banner);

        $this->confirmDelete = false;
        $this->deleteId      = null;

        session()->flash('success', 'Banner removido com sucesso.');
    }

    public function toggleActive(int $id): void
    {
        Banner::where('id', $id)->update(['is_active' => \Illuminate\Support\Facades\DB::raw('NOT is_active')]);
    }

    public function render(): \Illuminate\View\View
    {
        $banners = Banner::query()
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->orderBy('sort_order')
            ->paginate(15);

        return view('livewire.admin.banners.banner-index', compact('banners'))
            ->layout('admin.layouts.app', ['title' => 'Banners']);
    }
}

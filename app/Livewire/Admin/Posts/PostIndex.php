<?php

namespace App\Livewire\Admin\Posts;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\Post;
use App\Services\PostService;
use Livewire\Component;
use Livewire\WithPagination;

class PostIndex extends Component
{
    use AuthorizesAdminActions, WithPagination;

    public string $search        = '';
    public string $filterType    = '';
    public string $filterStatus  = '';
    public bool   $confirmDelete = false;
    public ?int   $deleteId      = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizeAdminAction('cms.editar');

        $this->deleteId      = $id;
        $this->confirmDelete = true;
    }

    public function deletePost(PostService $service): void
    {
        $this->authorizeAdminAction('cms.editar');

        $post = Post::findOrFail($this->deleteId);
        $service->delete($post);

        $this->confirmDelete = false;
        $this->deleteId      = null;

        session()->flash('success', 'Post removido.');
    }

    public function render(): \Illuminate\View\View
    {
        $posts = Post::with(['category', 'author'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.posts.post-index', [
            'posts'    => $posts,
            'types'    => PostType::cases(),
            'statuses' => PostStatus::cases(),
        ])->layout('admin.layouts.app', ['title' => 'Posts e Notícias']);
    }
}

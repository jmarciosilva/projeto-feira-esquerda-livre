<?php

namespace App\Livewire\Admin\Posts;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\ContentCategory;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class PostForm extends Component
{
    use WithFileUploads, ValidatesFileUploads;

    public ?Post $post = null;

    public int    $category_id      = 0;
    public string $title            = '';
    public string $slug             = '';
    public string $excerpt          = '';
    public string $content          = '';
    public string $meta_title       = '';
    public string $meta_description = '';
    public string $type             = 'post';
    public string $status           = 'draft';
    public string $published_at     = '';
    public bool   $is_active        = true;
    public ?string $image_path      = null;
    public $image_upload            = null;

    public function mount(?Post $post = null): void
    {
        if ($post && $post->exists) {
            $this->post             = $post;
            $this->category_id      = $post->category_id ?? 0;
            $this->title            = $post->title;
            $this->slug             = $post->slug;
            $this->excerpt          = $post->excerpt ?? '';
            $this->content          = $post->content ?? '';
            $this->meta_title       = $post->meta_title ?? '';
            $this->meta_description = $post->meta_description ?? '';
            $this->type             = $post->type->value;
            $this->status           = $post->status->value;
            $this->published_at     = $post->published_at?->format('Y-m-d\TH:i') ?? '';
            $this->is_active        = $post->is_active;
            $this->image_path       = $post->image_path;
        }
    }

    public function updatedTitle(): void
    {
        if (!$this->post) {
            $this->slug = \Illuminate\Support\Str::slug($this->title);
        }
    }

    public function save(PostService $service): void
    {
        if (! $this->checkUploadedFile($this->image_upload, 4096, 'image_upload')) return;

        $this->validate([
            'title'          => 'required|string|max:255',
            'type'           => 'required|in:post,news,campaign',
            'status'         => 'required|in:draft,published,archived',
            'image_upload'   => 'nullable|image|mimes:jpg,jpeg,png,gif,webp,svg',
            'published_at'   => 'nullable|date',
        ]);

        $data = [
            'user_id'          => auth()->id(),
            'category_id'      => $this->category_id ?: null,
            'title'            => $this->title,
            'slug'             => $this->slug ?: $this->title,
            'excerpt'          => $this->excerpt,
            'content'          => $this->content,
            'meta_title'       => $this->meta_title,
            'meta_description' => $this->meta_description,
            'type'             => $this->type,
            'status'           => $this->status,
            'published_at'     => $this->published_at ?: null,
            'is_active'        => $this->is_active,
        ];

        if ($this->image_upload) {
            if ($this->image_path) {
                Storage::disk('public')->delete($this->image_path);
            }
            $data['image_path'] = $this->image_upload->store('posts', 'public');
        }

        if ($this->post && $this->post->exists) {
            $service->update($this->post, $data);
            session()->flash('success', 'Post atualizado.');
        } else {
            $post = $service->create($data);
            session()->flash('success', 'Post criado.');
            $this->redirect(route('admin.posts.edit', $post));
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.posts.post-form', [
            'categories' => ContentCategory::where('is_active', true)->orderBy('name')->get(),
            'types'      => PostType::cases(),
            'statuses'   => PostStatus::cases(),
        ])->layout('admin.layouts.app', ['title' => $this->post ? 'Editar Post' : 'Novo Post']);
    }
}

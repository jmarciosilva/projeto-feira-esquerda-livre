<?php

namespace App\Livewire\Lojista\Feed;

use App\Enums\FeedPostType;
use App\Http\Requests\StoreFeedPostRequest;
use App\Livewire\Concerns\ValidatesFileUploads;
use App\Models\FeedPost;
use App\Services\FeedImageService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class FeedPostIndex extends Component
{
    use WithFileUploads, WithPagination, ValidatesFileUploads;

    public string $type = 'texto_livre';
    public string $content = '';
    public $upload1 = null;
    public $upload2 = null;
    public $upload3 = null;
    public $upload4 = null;

    public function save(FeedImageService $images): void
    {
        Gate::authorize('create', FeedPost::class);

        foreach (['upload1', 'upload2', 'upload3', 'upload4'] as $field) {
            if (! $this->checkUploadedFile($this->{$field}, 4096, $field)) {
                return;
            }
        }

        $this->validate(StoreFeedPostRequest::baseRules(), [], [
            'type' => 'tipo',
            'content' => 'texto',
        ]);

        $uploads = [$this->upload1, $this->upload2, $this->upload3, $this->upload4];

        auth()->user()->expositor->feedPosts()->create([
            'type' => $this->type,
            'content' => trim($this->content),
            'images' => $images->storeMany($uploads),
            'is_visible' => true,
            'reported_count' => 0,
        ]);

        $this->reset(['type', 'content', 'upload1', 'upload2', 'upload3', 'upload4']);
        $this->type = 'texto_livre';
        session()->flash('success', 'Publicação enviada para o feed.');
    }

    public function delete(int $postId, FeedImageService $images): void
    {
        $post = $this->ownPosts()->findOrFail($postId);
        Gate::authorize('delete', $post);

        $images->deleteMany($post->images ?? []);
        $post->delete();

        session()->flash('success', 'Publicação removida.');
    }

    private function ownPosts()
    {
        return FeedPost::where('expositor_id', auth()->user()->expositor->id);
    }

    public function render(): View
    {
        $types = FeedPostType::cases();
        $posts = $this->ownPosts()
            ->withCount(['likes', 'comments', 'reports'])
            ->latest()
            ->paginate(10);

        return view('livewire.lojista.feed.feed-post-index', compact('types', 'posts'))
            ->layout('lojista.layouts.app', ['title' => 'Comunidade']);
    }
}

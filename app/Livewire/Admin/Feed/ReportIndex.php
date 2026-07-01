<?php

namespace App\Livewire\Admin\Feed;

use App\Models\FeedModerationLog;
use App\Models\FeedPost;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ReportIndex extends Component
{
    use WithPagination;

    public array $moderationReason = [];
    public string $filter = 'pendentes';

    public function hidePost(int $postId): void
    {
        $post = FeedPost::findOrFail($postId);
        Gate::authorize('moderate', FeedPost::class);

        $field = "moderationReason.{$postId}";
        $this->validate([$field => 'required|string|max:500'], [], [$field => 'motivo']);

        $post->update(['is_visible' => false]);
        $post->reports()->where('status', 'pendente')->update(['status' => 'resolvido']);
        FeedModerationLog::create([
            'feed_post_id' => $post->id,
            'admin_user_id' => auth()->id(),
            'action' => 'ocultar',
            'reason' => trim($this->moderationReason[$postId]),
        ]);

        $this->moderationReason[$postId] = '';
        session()->flash('success', 'Publicação ocultada e reportes resolvidos.');
    }

    public function restorePost(int $postId): void
    {
        $post = FeedPost::findOrFail($postId);
        Gate::authorize('moderate', FeedPost::class);

        $field = "moderationReason.{$postId}";
        $this->validate([$field => 'required|string|max:500'], [], [$field => 'motivo']);

        $post->update(['is_visible' => true]);
        FeedModerationLog::create([
            'feed_post_id' => $post->id,
            'admin_user_id' => auth()->id(),
            'action' => 'restaurar',
            'reason' => trim($this->moderationReason[$postId]),
        ]);

        $this->moderationReason[$postId] = '';
        session()->flash('success', 'Publicação restaurada.');
    }

    public function render(): View
    {
        $posts = FeedPost::query()
            ->with(['expositor', 'reports' => fn ($q) => $q->latest(), 'moderationLogs.admin'])
            ->withCount(['reports', 'likes', 'comments'])
            ->when($this->filter === 'pendentes', fn ($q) => $q->whereHas('reports', fn ($r) => $r->where('status', 'pendente')))
            ->when($this->filter === 'ocultas', fn ($q) => $q->where('is_visible', false))
            ->orderByDesc('reported_count')
            ->latest()
            ->paginate(12);

        $pendingCount = FeedPost::whereHas('reports', fn ($q) => $q->where('status', 'pendente'))->count();

        return view('livewire.admin.feed.report-index', compact('posts', 'pendingCount'))
            ->layout('admin.layouts.app', ['title' => 'Moderação da Comunidade']);
    }
}

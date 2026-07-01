<?php

namespace App\Livewire\Feed;

use App\Http\Requests\ReportFeedPostRequest;
use App\Http\Requests\StoreFeedCommentRequest;
use App\Models\FeedPost;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class FeedIndex extends Component
{
    use WithPagination;

    public array $commentText = [];
    public ?int $reportingPostId = null;
    public string $reportReason = '';

    public function refreshFeed(): void {}

    public function toggleLike(int $postId): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: false);
            return;
        }

        $post = FeedPost::visible()->findOrFail($postId);
        Gate::authorize('interact', $post);

        $like = $post->likes()->where('user_id', auth()->id())->first();

        if ($like) {
            $like->delete();
            return;
        }

        $post->likes()->create([
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    public function addComment(int $postId): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: false);
            return;
        }

        $post = FeedPost::visible()->findOrFail($postId);
        Gate::authorize('create', [\App\Models\FeedComment::class, $post]);

        $field = "commentText.{$postId}";
        $this->validate(StoreFeedCommentRequest::baseRules($field), [], [
            $field => 'comentário',
        ]);

        $post->comments()->create([
            'user_id' => auth()->id(),
            'content' => trim($this->commentText[$postId]),
            'is_visible' => true,
        ]);

        $this->commentText[$postId] = '';
    }

    public function openReport(int $postId): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: false);
            return;
        }

        $this->reportingPostId = $postId;
        $this->reportReason = '';
    }

    public function closeReport(): void
    {
        $this->reportingPostId = null;
        $this->reportReason = '';
        $this->resetErrorBag('reportReason');
    }

    public function report(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: false);
            return;
        }

        $post = FeedPost::visible()->findOrFail($this->reportingPostId);
        Gate::authorize('report', $post);
        $this->validate(ReportFeedPostRequest::baseRules(), [], [
            'reportReason' => 'motivo',
        ]);

        $report = $post->reports()->firstOrCreate(
            ['user_id' => auth()->id()],
            ['reason' => trim($this->reportReason), 'status' => 'pendente']
        );

        if ($report->wasRecentlyCreated) {
            $post->increment('reported_count');
            session()->flash('success', 'Denúncia enviada para moderação.');
        } else {
            session()->flash('success', 'Você já denunciou esta publicação.');
        }

        $this->closeReport();
    }

    public function render(): View
    {
        $posts = FeedPost::visible()
            ->with([
                'expositor',
                'likes',
                'comments' => fn ($q) => $q->visible()->with('user')->latest(),
            ])
            ->withCount([
                'likes',
                'comments' => fn ($q) => $q->visible(),
            ])
            ->latest()
            ->paginate(10);

        return view('livewire.feed.feed-index', compact('posts'));
    }
}

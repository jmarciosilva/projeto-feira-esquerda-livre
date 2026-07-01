<?php

namespace App\Policies;

use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\User;

class FeedCommentPolicy
{
    public function create(User $user, FeedPost $post): bool
    {
        return $post->is_visible && $user->is_active;
    }

    public function hide(User $user, FeedComment $comment): bool
    {
        return $user->isEditor();
    }
}

<?php

namespace App\Policies;

use App\Models\FeedPost;
use App\Models\User;

class FeedPostPolicy
{
    public function create(User $user): bool
    {
        return $user->isLojista() && (bool) $user->expositor?->is_active;
    }

    public function update(User $user, FeedPost $post): bool
    {
        return $user->isEditor()
            || ($user->isLojista() && $user->expositor?->id === $post->expositor_id);
    }

    public function delete(User $user, FeedPost $post): bool
    {
        return $this->update($user, $post);
    }

    public function interact(User $user, FeedPost $post): bool
    {
        return $post->is_visible && $user->is_active;
    }

    public function report(User $user, FeedPost $post): bool
    {
        return $post->is_visible && $user->is_active;
    }

    public function moderate(User $user): bool
    {
        return $user->isEditor();
    }
}

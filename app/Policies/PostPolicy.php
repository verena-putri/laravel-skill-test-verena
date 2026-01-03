<?php

namespace App\Policies;

use App\Models\User;

class PostPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        if ($post->isDraft() || $post->isScheduled()) {
            return $user->id === $post->user_id;
        }

        return $post->isPublished();
    }

    public function create(User $user): bool
    {
        return ! is_null($user);
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function edit(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}

<?php

namespace App\Features\Todo\Policies;

use App\Features\Todo\Models\Todo;
use App\Models\User;

class TodoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMember();
    }

    /**
     * Pemilik dapat melihat todo miliknya; admin dapat melihat (read-only).
     */
    public function view(User $user, Todo $todo): bool
    {
        return $user->isAdmin() || $user->id === $todo->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isMember();
    }

    public function update(User $user, Todo $todo): bool
    {
        return $user->isMember() && $user->id === $todo->user_id;
    }

    public function delete(User $user, Todo $todo): bool
    {
        return $this->update($user, $todo);
    }
}

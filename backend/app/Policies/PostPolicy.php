<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, Post $model): bool
    {
        if (!$user) {
            return false;
        }
        if (in_array($user->system_role, ['admin', 'coordinator'])) {
            return true;
        }
        return $model->organization->members()->where('users.id', $user->id)->exists();
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return in_array($user->system_role, ['teacher', 'admin']);
    }

    public function update(?User $user, Post $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        if ($user->system_role === 'teacher') {
            return $model->organization->members()->where('users.id', $user->id)->exists();
        }
        return false;
    }

    public function delete(?User $user, Post $model): bool
    {
        return $this->update($user, $model);
    }
}

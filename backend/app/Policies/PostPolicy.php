<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use App\Models\Organization;

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

    public function create(?User $user, Organization $organization): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        return $organization->members()->whereKey($user->id)->wherePivot('role', 'representative')->exists()
            || ($user->system_role === 'teacher' && $organization->members()->whereKey($user->id)->wherePivot('role', 'teacher')->exists());
    }

    public function update(?User $user, Post $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        return $this->create($user, $model->organization);
    }

    public function delete(?User $user, Post $model): bool
    {
        return $this->update($user, $model);
    }
}

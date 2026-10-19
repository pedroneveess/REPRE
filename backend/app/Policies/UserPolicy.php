<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewGlobalDashboard(?User $user): bool
    {
        return $user !== null && in_array($user->system_role, ['admin', 'coordinator'], true);
    }

    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, User $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        if ($user->id === $model->id) {
            return true;
        }
        return $user->system_role === 'teacher'
            && $user->schoolClasses()->wherePivot('role', 'teacher')->whereHas('members', fn ($members) => $members->where('users.id', $model->id))->exists();
    }

    public function create(?User $user): bool
    {
        return $user?->system_role === 'admin';
    }

    public function update(?User $user, User $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        return false;
    }

    public function delete(?User $user, User $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        return false;
    }
}

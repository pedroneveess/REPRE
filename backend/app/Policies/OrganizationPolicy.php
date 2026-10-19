<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, Organization $model): bool
    {
        if (!$user) {
            return false;
        }
        if (in_array($user->system_role, ['admin', 'coordinator'])) {
            return true;
        }
        return $model->members()->where('users.id', $user->id)->exists();
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return in_array($user->system_role, ['teacher', 'admin']);
    }

    public function update(?User $user, Organization $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        if ($user->system_role === 'teacher') {
            return $model->members()->whereKey($user->id)->wherePivot('role', 'teacher')->exists();
        }
        return false;
    }

    public function delete(?User $user, Organization $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        if ($user->system_role === 'teacher') {
            return $model->members()->whereKey($user->id)->wherePivot('role', 'teacher')->exists();
        }
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SchoolClassPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function viewPerformance(?User $user, SchoolClass $model): bool
    {
        return $user?->system_role === 'admin'
            || ($user?->system_role === 'teacher' && $model->members()->whereKey($user->id)->where('class_members.role', 'teacher')->exists());
    }

    public function view(?User $user, SchoolClass $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        if ($user->system_role === 'coordinator') {
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

    public function update(?User $user, SchoolClass $model): bool
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

    public function delete(?User $user, SchoolClass $model): bool
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

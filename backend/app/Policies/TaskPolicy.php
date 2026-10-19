<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Models\SchoolClass;

class TaskPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, Task $model): bool
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
        return $model->schoolClass->members()->where('users.id', $user->id)->exists();
    }

    public function create(?User $user, SchoolClass $schoolClass): bool
    {
        return $this->canManage($user, $schoolClass);
    }

    public function update(?User $user, Task $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        return $this->canManage($user, $model->schoolClass);
    }

    public function delete(?User $user, Task $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        return $this->canManage($user, $model->schoolClass);
    }

    private function canManage(?User $user, SchoolClass $schoolClass): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        $role = $user->system_role === 'teacher' ? 'teacher' : 'representative';
        return $schoolClass->members()->whereKey($user->id)->wherePivot('role', $role)->exists();
    }
}

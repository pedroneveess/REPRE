<?php

namespace App\Policies;

use App\Models\TaskCompletion;
use App\Models\User;
use App\Models\Task;
use Illuminate\Auth\Access\Response;

class TaskCompletionPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, TaskCompletion $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        if ($model->user_id === $user->id) {
            return true;
        }
        if ($user->system_role === 'teacher') {
            return $model->task->schoolClass->members()
                ->whereKey($user->id)
                ->wherePivot('role', 'teacher')
                ->exists();
        }
        return false;
    }

    public function create(?User $user, Task $task): bool
    {
        if (!$user) {
            return false;
        }
        return $user->system_role === 'admin'
            || (in_array($user->system_role, ['student', 'representative', 'teacher'], true)
                && $task->schoolClass->members()->whereKey($user->id)->exists());
    }

    public function update(?User $user, TaskCompletion $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        if ($user->system_role === 'coordinator') {
            return false;
        }
        return $model->user_id === $user->id;
    }

    public function delete(?User $user, TaskCompletion $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        if ($user->system_role === 'coordinator') {
            return false;
        }
        return $model->user_id === $user->id;
    }
}

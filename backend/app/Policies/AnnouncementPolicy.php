<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AnnouncementPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, Announcement $model): bool
    {
        if (!$user) {
            return false;
        }
        if (in_array($user->system_role, ['admin', 'coordinator'])) {
            return true;
        }
        return $model->schoolClass->members()->where('users.id', $user->id)->exists();
    }

    public function create(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return in_array($user->system_role, ['teacher', 'admin']);
    }

    public function update(?User $user, Announcement $model): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        if ($user->system_role === 'teacher') {
            return $model->schoolClass->members()->where('users.id', $user->id)->exists();
        }
        return false;
    }

    public function delete(?User $user, Announcement $model): bool
    {
        return $this->update($user, $model);
    }
}

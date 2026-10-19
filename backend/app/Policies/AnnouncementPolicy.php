<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;
use App\Models\SchoolClass;

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

    public function create(?User $user, SchoolClass $schoolClass): bool
    {
        if (!$user) {
            return false;
        }
        return $user->system_role === 'admin' || ($user->system_role === 'teacher' && $schoolClass->members()->whereKey($user->id)->where('class_members.role', 'teacher')->exists());
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
            return $model->schoolClass->members()->whereKey($user->id)->where('class_members.role', 'teacher')->exists();
        }
        return false;
    }

    public function delete(?User $user, Announcement $model): bool
    {
        return $this->update($user, $model);
    }
}

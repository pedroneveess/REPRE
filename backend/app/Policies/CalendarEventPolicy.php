<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\SchoolClass;

class CalendarEventPolicy
{
    public function viewAny(?User $user): bool
    {
        return $user !== null;
    }

    public function view(?User $user, CalendarEvent $event): bool
    {
        if (!$user) {
            return false;
        }
        if (in_array($user->system_role, ['admin', 'coordinator'], true) || $event->class_id === null) {
            return true;
        }
        return $event->schoolClass->members()->where('users.id', $user->id)->exists();
    }

    public function create(?User $user, SchoolClass $schoolClass): bool
    {
        return $user && ($user->system_role === 'admin' || ($user->system_role === 'teacher' && $schoolClass->members()->whereKey($user->id)->where('class_members.role', 'teacher')->exists()));
    }

    public function update(?User $user, CalendarEvent $event): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->system_role === 'admin') {
            return true;
        }
        return $user->system_role === 'teacher'
            && $event->class_id !== null
            && $event->schoolClass->members()->whereKey($user->id)->where('class_members.role', 'teacher')->exists();
    }

    public function delete(?User $user, CalendarEvent $event): bool
    {
        return $this->update($user, $event);
    }
}

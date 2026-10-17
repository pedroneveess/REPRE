<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

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

    public function create(?User $user): bool
    {
        return $user && in_array($user->system_role, ['admin', 'teacher'], true);
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
            && $event->schoolClass->members()->where('users.id', $user->id)->exists();
    }

    public function delete(?User $user, CalendarEvent $event): bool
    {
        return $this->update($user, $event);
    }
}

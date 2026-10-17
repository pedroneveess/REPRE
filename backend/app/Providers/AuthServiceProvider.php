<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Organization;
use App\Models\Post;
use App\Models\SchoolClass;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => \App\Policies\UserPolicy::class,
        SchoolClass::class => \App\Policies\SchoolClassPolicy::class,
        Task::class => \App\Policies\TaskPolicy::class,
        TaskCompletion::class => \App\Policies\TaskCompletionPolicy::class,
        Announcement::class => \App\Policies\AnnouncementPolicy::class,
        CalendarEvent::class => \App\Policies\CalendarEventPolicy::class,
        Organization::class => \App\Policies\OrganizationPolicy::class,
        Post::class => \App\Policies\PostPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('isTeacher', function (User $user) {
            return $user->system_role === 'teacher';
        });

        Gate::define('isRepresentative', function (User $user) {
            return in_array($user->system_role, ['teacher', 'representative']);
        });

        Gate::define('isClassMember', function (User $user, SchoolClass $schoolClass) {
            return $schoolClass->members()->where('users.id', $user->id)->exists();
        });

        Gate::define('isOrganizationMember', function (User $user, Organization $organization) {
            return $organization->members()->where('users.id', $user->id)->exists();
        });
    }
}

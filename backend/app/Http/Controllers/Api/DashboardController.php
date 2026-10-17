<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Organization;
use App\Models\Post;
use App\Models\SchoolClass;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;

class DashboardController extends Controller
{
    public function show()
    {
        $this->authorize('viewGlobalDashboard', User::class);

        return response()->json([
            'summary' => [
                'users' => User::count(),
                'classes' => SchoolClass::count(),
                'class_memberships' => SchoolClass::query()->join('class_members', 'school_classes.id', '=', 'class_members.class_id')->count(),
                'organizations' => Organization::count(),
                'organization_memberships' => Organization::query()->join('organization_members', 'organizations.id', '=', 'organization_members.organization_id')->count(),
                'tasks' => Task::count(),
                'task_completions' => TaskCompletion::count(),
                'announcements' => Announcement::count(),
                'calendar_events' => CalendarEvent::count(),
                'posts' => Post::count(),
            ],
            'classes' => SchoolClass::query()
                ->withCount(['members', 'tasks'])
                ->orderBy('name')
                ->get(['id', 'name', 'course', 'year']),
            'upcoming_events' => CalendarEvent::query()
                ->with('schoolClass:id,name')
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->limit(5)
                ->get(['id', 'title', 'starts_at', 'ends_at', 'class_id']),
            'recent_announcements' => Announcement::query()
                ->with('schoolClass:id,name')
                ->latest()
                ->limit(5)
                ->get(['id', 'title', 'class_id', 'created_at']),
        ]);
    }
}

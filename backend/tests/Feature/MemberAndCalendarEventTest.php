<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Organization;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberAndCalendarEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_endpoints_apply_view_and_mutation_policies(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);
        $outsider = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($teacher->id, ['role' => 'teacher']);
        $class->members()->attach($student->id, ['role' => 'student']);
        $organization = Organization::factory()->create();
        $organization->members()->attach($teacher->id, ['role' => 'teacher']);
        $organization->members()->attach($student->id, ['role' => 'member']);

        $this->asUser($student)->getJson('/api/school-classes/'.$class->id.'/members')->assertOk();
        $this->asUser($outsider)->getJson('/api/school-classes/'.$class->id.'/members')->assertForbidden();
        $this->asUser($teacher)->postJson('/api/school-classes/'.$class->id.'/members', ['user_id' => $outsider->id, 'role' => 'student'])
            ->assertCreated();
        $this->assertDatabaseHas('users', ['id' => $outsider->id, 'system_role' => 'student']);
        $this->asUser($teacher)->postJson('/api/school-classes/'.$class->id.'/members', ['user_id' => $outsider->id, 'role' => 'administrator'])
            ->assertUnprocessable();
        $this->asUser($teacher)->postJson('/api/organizations/'.$organization->id.'/members', ['user_id' => $outsider->id, 'role' => 'representative'])
            ->assertCreated();
        $this->asUser($teacher)->postJson('/api/organizations/'.$organization->id.'/members', ['user_id' => $outsider->id, 'role' => 'coordinator'])
            ->assertUnprocessable();
        $otherClass = SchoolClass::factory()->create();
        $this->asUser($teacher)->postJson('/api/school-classes/'.$otherClass->id.'/members', ['user_id' => $outsider->id, 'role' => 'student'])
            ->assertForbidden();
    }

    public function test_admin_can_manage_members_and_coordinator_is_read_only(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $coordinator = User::factory()->create(['system_role' => 'coordinator']);
        $member = User::factory()->create();
        $class = SchoolClass::factory()->create();
        $organization = Organization::factory()->create();

        $this->asUser($admin)->postJson('/api/school-classes/'.$class->id.'/members', ['user_id' => $member->id, 'role' => 'representative'])
            ->assertCreated();
        $this->asUser($admin)->postJson('/api/organizations/'.$organization->id.'/members', ['user_id' => $member->id, 'role' => 'teacher'])
            ->assertCreated();
        $this->asUser($coordinator)->getJson('/api/school-classes/'.$class->id.'/members')->assertOk();
        $this->asUser($coordinator)->postJson('/api/organizations/'.$organization->id.'/members', ['user_id' => $member->id, 'role' => 'member'])
            ->assertForbidden();
        $this->asUser($admin)->postJson('/api/organizations/'.$organization->id.'/members', ['user_id' => 'not-a-uuid', 'role' => 'member'])
            ->assertUnprocessable();
    }

    public function test_calendar_event_visibility_and_writes_follow_roles_and_class_membership(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);
        $otherStudent = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($teacher->id, ['role' => 'teacher']);
        $class->members()->attach($student->id, ['role' => 'student']);
        $outsideClass = SchoolClass::factory()->create();
        $global = CalendarEvent::factory()->global()->create();
        $visible = CalendarEvent::factory()->create(['class_id' => $class->id]);
        $hidden = CalendarEvent::factory()->create(['class_id' => $outsideClass->id]);

        $this->asUser($student)->getJson('/api/calendar-events')
            ->assertOk()
            ->assertJsonFragment(['id' => $global->id])
            ->assertJsonFragment(['id' => $visible->id])
            ->assertJsonMissing(['id' => $hidden->id]);
        $this->asUser($otherStudent)->getJson('/api/calendar-events/'.$global->id)->assertOk();
        $this->asUser($otherStudent)->getJson('/api/calendar-events/'.$hidden->id)->assertForbidden();
        $this->asUser($teacher)->postJson('/api/calendar-events', [
            'title' => 'Class event', 'description' => 'Details', 'starts_at' => '2026-11-01T10:00:00Z', 'class_id' => $class->id,
        ])->assertCreated();
        $this->asUser($teacher)->postJson('/api/calendar-events', [
            'title' => 'Global event', 'description' => 'Details', 'starts_at' => '2026-11-01T10:00:00Z',
        ])->assertForbidden();
        $this->asUser($teacher)->putJson('/api/calendar-events/'.$hidden->id, ['title' => 'IDOR'])->assertForbidden();
        $this->asUser($teacher)->putJson('/api/calendar-events/'.$visible->id, [
            'starts_at' => '2026-11-01T11:00:00Z', 'ends_at' => '2026-11-01T10:00:00Z',
        ])->assertUnprocessable();
        $coordinator = User::factory()->create(['system_role' => 'coordinator']);
        $this->asUser($coordinator)->getJson('/api/calendar-events')
            ->assertOk()
            ->assertJsonFragment(['id' => $hidden->id]);
        $this->asUser($coordinator)->putJson('/api/calendar-events/'.$global->id, ['title' => 'Changed'])->assertForbidden();
    }
}


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

    private function actingAsApi(User $user): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('feature')->plainTextToken);
    }

    public function test_member_endpoints_apply_view_and_mutation_policies_and_validate_scope(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);
        $outsider = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($teacher->id, ['role' => 'teacher']);
        $class->members()->attach($student->id, ['role' => 'student']);
        $organization = Organization::factory()->create();
        $organization->members()->attach($teacher->id, ['role' => 'administrator']);
        $organization->members()->attach($student->id, ['role' => 'member']);

        $this->actingAsApi($student)->getJson('/api/school_classes/'.$class->id.'/members')->assertOk();
        $this->actingAsApi($outsider)->getJson('/api/school_classes/'.$class->id.'/members')->assertForbidden();
        $this->actingAsApi($teacher)->postJson('/api/school_classes/'.$class->id.'/members', ['user_id' => $outsider->id, 'role' => 'student', 'system_role' => 'admin'])->assertCreated();
        $this->assertDatabaseHas('users', ['id' => $outsider->id, 'system_role' => 'student']);
        $this->actingAsApi($teacher)->postJson('/api/school_classes/'.$class->id.'/members', ['user_id' => $outsider->id, 'role' => 'administrator'])->assertUnprocessable();
        $this->actingAsApi($teacher)->postJson('/api/organizations/'.$organization->id.'/members', ['user_id' => $outsider->id, 'role' => 'president'])->assertCreated();
        $this->actingAsApi($teacher)->postJson('/api/organizations/'.$organization->id.'/members', ['user_id' => $outsider->id, 'role' => 'teacher'])->assertUnprocessable();
        $otherClass = SchoolClass::factory()->create();
        $this->actingAsApi($teacher)->postJson('/api/school_classes/'.$otherClass->id.'/members', ['user_id' => $outsider->id, 'role' => 'student'])->assertForbidden();
    }

    public function test_admin_can_manage_members_and_coordinator_is_read_only(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $coordinator = User::factory()->create(['system_role' => 'coordinator']);
        $member = User::factory()->create();
        $class = SchoolClass::factory()->create();
        $organization = Organization::factory()->create();

        $this->actingAsApi($admin)->postJson('/api/school_classes/'.$class->id.'/members', ['user_id' => $member->id, 'role' => 'representative'])->assertCreated();
        $this->actingAsApi($admin)->postJson('/api/organizations/'.$organization->id.'/members', ['user_id' => $member->id, 'role' => 'coordinator'])->assertCreated();
        $this->actingAsApi($coordinator)->getJson('/api/school_classes/'.$class->id.'/members')->assertOk();
        $this->actingAsApi($coordinator)->postJson('/api/organizations/'.$organization->id.'/members', ['user_id' => $member->id, 'role' => 'member'])->assertForbidden();
        $this->actingAsApi($admin)->postJson('/api/organizations/'.$organization->id.'/members', ['user_id' => 'not-a-uuid', 'role' => 'member'])->assertUnprocessable();
    }

    public function test_calendar_event_visibility_and_writes_follow_roles_and_class_membership(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);
        $otherStudent = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($teacher->id);
        $class->members()->attach($student->id);
        $outsideClass = SchoolClass::factory()->create();
        $global = CalendarEvent::factory()->global()->create();
        $visible = CalendarEvent::factory()->create(['class_id' => $class->id]);
        $hidden = CalendarEvent::factory()->create(['class_id' => $outsideClass->id]);

        $this->actingAsApi($student)->getJson('/api/calendar_events')->assertOk()->assertJsonFragment(['id' => $global->id])->assertJsonFragment(['id' => $visible->id])->assertJsonMissing(['id' => $hidden->id]);
        $this->actingAsApi($otherStudent)->getJson('/api/calendar_events/'.$global->id)->assertOk();
        $this->actingAsApi($otherStudent)->getJson('/api/calendar_events/'.$hidden->id)->assertForbidden();
        $this->actingAsApi($teacher)->postJson('/api/calendar_events', ['title' => 'Class event', 'description' => 'Details', 'starts_at' => '2026-11-01T10:00:00Z', 'class_id' => $class->id])->assertCreated();
        $this->actingAsApi($teacher)->postJson('/api/calendar_events', ['title' => 'Global event', 'description' => 'Details', 'starts_at' => '2026-11-01T10:00:00Z'])->assertForbidden();
        $this->actingAsApi($teacher)->putJson('/api/calendar_events/'.$hidden->id, ['title' => 'IDOR'])->assertForbidden();
        $this->actingAsApi($teacher)->putJson('/api/calendar_events/'.$visible->id, ['starts_at' => '2026-11-01T11:00:00Z', 'ends_at' => '2026-11-01T10:00:00Z'])->assertUnprocessable();
        $coordinator = User::factory()->create(['system_role' => 'coordinator']);
        $this->actingAsApi($coordinator)->getJson('/api/calendar_events')->assertOk()->assertJsonFragment(['id' => $hidden->id]);
        $this->actingAsApi($coordinator)->putJson('/api/calendar_events/'.$global->id, ['title' => 'Changed'])->assertForbidden();
    }
}

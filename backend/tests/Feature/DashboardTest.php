<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsApi(User $user): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('feature')->plainTextToken);
    }

    public function test_coordinator_and_admin_can_view_global_dashboard(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($teacher->id, ['role' => 'teacher']);
        CalendarEvent::factory()->create(['class_id' => $class->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
        $coordinator = User::factory()->create(['system_role' => 'coordinator']);
        $admin = User::factory()->create(['system_role' => 'admin']);

        foreach ([$coordinator, $admin] as $user) {
            $response = $this->actingAsApi($user)->getJson('/api/dashboard')->assertOk();
            $response->assertJsonPath('summary.users', 3);
            $response->assertJsonPath('summary.classes', 1);
            $response->assertJsonPath('summary.calendar_events', 1);
            $response->assertJsonFragment(['id' => $class->id, 'members_count' => 1]);
        }
    }

    public function test_regular_user_cannot_view_global_dashboard(): void
    {
        $student = User::factory()->create(['system_role' => 'student']);
        $this->actingAsApi($student)->getJson('/api/dashboard')->assertForbidden();
    }
}

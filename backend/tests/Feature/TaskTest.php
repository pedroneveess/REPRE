<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Lista', 'due_date' => '2026-12-31',
        ], $overrides);
    }

    public function test_teacher_with_pivot_can_create_task(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($user->id, ['role' => 'teacher']);

        $response = $this->asUser($user)->postJson('/api/tasks', $this->payload(['class_id' => $class->id]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => 'Lista']);
    }

    public function test_student_member_cannot_create_task(): void
    {
        $user = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($user->id, ['role' => 'student']);

        $response = $this->asUser($user)->postJson('/api/tasks', $this->payload(['class_id' => $class->id]));

        $response->assertStatus(403);
    }

    public function test_representative_with_pivot_can_create_task(): void
    {
        $user = User::factory()->create(['system_role' => 'representative']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($user->id, ['role' => 'representative']);

        $response = $this->asUser($user)->postJson('/api/tasks', $this->payload(['class_id' => $class->id]));

        $response->assertStatus(201);
    }

    public function test_representative_pivot_works_for_any_global_role(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($user->id, ['role' => 'representative']);

        $this->asUser($user)->postJson('/api/tasks', $this->payload(['class_id' => $class->id]))
            ->assertStatus(201);
    }

    public function test_admin_can_create_task_without_membership(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $class = SchoolClass::factory()->create();

        $response = $this->asUser($admin)->postJson('/api/tasks', $this->payload(['class_id' => $class->id]));

        $response->assertStatus(201);
    }

    public function test_teacher_cannot_create_task_in_unrelated_class(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $memberClass = SchoolClass::factory()->create();
        $otherClass = SchoolClass::factory()->create();
        $memberClass->members()->attach($teacher->id, ['role' => 'teacher']);

        $this->asUser($teacher)->postJson('/api/tasks', $this->payload(['class_id' => $otherClass->id]))
            ->assertStatus(403);
    }

    public function test_class_member_can_view_task_but_outsider_cannot(): void
    {
        $student = User::factory()->create(['system_role' => 'student']);
        $outsider = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($student->id, ['role' => 'student']);
        $task = Task::factory()->create(['class_id' => $class->id]);

        $this->asUser($student)->getJson('/api/tasks/'.$task->id)->assertStatus(200);
        $this->asUser($outsider)->getJson('/api/tasks/'.$task->id)->assertStatus(403);
    }

    public function test_teacher_pivot_can_update_task(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($user->id, ['role' => 'teacher']);
        $task = Task::factory()->create(['class_id' => $class->id, 'created_by' => $user->id]);

        $response = $this->asUser($user)->putJson('/api/tasks/'.$task->id, ['title' => 'Atualizada']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Atualizada']);
    }

    public function test_invalid_uuid_returns_404(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);

        $this->asUser($admin)->getJson('/api/tasks/not-a-uuid')->assertStatus(404);
    }
}

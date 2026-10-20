<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_complete_task(): void
    {
        $student = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($student->id, ['role' => 'student']);
        $task = Task::factory()->create(['class_id' => $class->id]);

        $response = $this->asUser($student)->postJson('/api/task-completions', ['task_id' => $task->id]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('task_completions', ['user_id' => $student->id, 'task_id' => $task->id]);
    }

    public function test_non_member_cannot_complete_task(): void
    {
        $outsider = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $task = Task::factory()->create(['class_id' => $class->id]);

        $response = $this->asUser($outsider)->postJson('/api/task-completions', ['task_id' => $task->id]);

        $response->assertStatus(403);
    }

    public function test_duplicate_completion_returns_409(): void
    {
        $student = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($student->id, ['role' => 'student']);
        $task = Task::factory()->create(['class_id' => $class->id]);
        TaskCompletion::factory()->create(['user_id' => $student->id, 'task_id' => $task->id]);

        $response = $this->asUser($student)->postJson('/api/task-completions', ['task_id' => $task->id]);

        $response->assertStatus(409);
        $this->assertDatabaseCount('task_completions', 1);
    }

    public function test_student_sees_only_own_completions(): void
    {
        $student = User::factory()->create(['system_role' => 'student']);
        $other = User::factory()->create(['system_role' => 'student']);
        $own = TaskCompletion::factory()->create(['user_id' => $student->id]);
        $otherCompletion = TaskCompletion::factory()->create(['user_id' => $other->id]);

        $response = $this->asUser($student)->getJson('/api/task-completions');

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $own->id]);
        $response->assertJsonMissing(['id' => $otherCompletion->id]);
    }

    public function test_student_cannot_view_other_completion(): void
    {
        $student = User::factory()->create(['system_role' => 'student']);
        $other = User::factory()->create(['system_role' => 'student']);
        $completion = TaskCompletion::factory()->create(['user_id' => $other->id]);

        $this->asUser($student)->getJson('/api/task-completions/'.$completion->id)->assertStatus(403);
    }

    public function test_delete_task_with_completions_returns_409_and_keeps_tokens(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $task = TaskCompletion::factory()->create()->task;

        $response = $this->asUser($admin)->deleteJson('/api/tasks/'.$task->id);

        $response->assertStatus(409);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
        $this->assertGreaterThan(0, $admin->tokens()->count());
    }

    public function test_invalid_uuid_returns_404(): void
    {
        $student = User::factory()->create(['system_role' => 'student']);

        $this->asUser($student)->postJson('/api/task-completions', ['task_id' => 'not-a-uuid'])
            ->assertStatus(422);

        $this->asUser($student)->getJson('/api/task-completions/not-a-uuid')->assertStatus(404);
    }
}

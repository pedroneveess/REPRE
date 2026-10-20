<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_access_another_student_task_completion(): void
    {
        $student1 = User::factory()->create(['system_role' => 'student']);
        $student2 = User::factory()->create(['system_role' => 'student']);

        $task = Task::factory()->create();
        $completion = \App\Models\TaskCompletion::factory()->create([
            'user_id' => $student1->id,
            'task_id' => $task->id,
        ]);

        $this->asUser($student2)->getJson('/api/task-completions/'.$completion->id)->assertStatus(403);
    }

    public function test_teacher_cannot_access_task_completion_outside_class(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);

        $task = Task::factory()->create();
        $completion = \App\Models\TaskCompletion::factory()->create([
            'user_id' => $student->id,
            'task_id' => $task->id,
        ]);

        $this->asUser($teacher)->getJson('/api/task-completions/'.$completion->id)->assertStatus(403);
    }

    public function test_teacher_member_can_access_task_completion_in_class(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);

        $schoolClass = SchoolClass::factory()->create();
        $schoolClass->members()->attach($teacher->id, ['role' => 'teacher']);
        $schoolClass->members()->attach($student->id, ['role' => 'student']);

        $task = Task::factory()->create(['class_id' => $schoolClass->id]);
        $completion = \App\Models\TaskCompletion::factory()->create([
            'user_id' => $student->id,
            'task_id' => $task->id,
        ]);

        $this->asUser($teacher)->getJson('/api/task-completions/'.$completion->id)->assertStatus(200);
    }

    public function test_teacher_index_lists_only_completions_from_member_classes(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);
        $otherStudent = User::factory()->create(['system_role' => 'student']);

        $memberClass = SchoolClass::factory()->create();
        $memberClass->members()->attach($teacher->id, ['role' => 'teacher']);
        $memberClass->members()->attach($student->id, ['role' => 'student']);

        $otherClass = SchoolClass::factory()->create();
        $otherClass->members()->attach($otherStudent->id, ['role' => 'student']);

        $memberTask = Task::factory()->create(['class_id' => $memberClass->id]);
        $otherTask = Task::factory()->create(['class_id' => $otherClass->id]);

        $memberCompletion = \App\Models\TaskCompletion::factory()->create([
            'user_id' => $student->id,
            'task_id' => $memberTask->id,
        ]);
        \App\Models\TaskCompletion::factory()->create([
            'user_id' => $otherStudent->id,
            'task_id' => $otherTask->id,
        ]);

        $response = $this->asUser($teacher)->getJson('/api/task-completions');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $memberCompletion->id]);
    }

    public function test_user_cannot_modify_another_user_account(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->asUser($user2)->putJson('/api/auth/me', ['name' => 'Hackeado'])->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user1->id, 'name' => $user1->name]);
        $this->assertDatabaseMissing('users', ['id' => $user1->id, 'name' => 'Hackeado']);
    }

    public function test_unauthenticated_cannot_access_any_resource(): void
    {
        $endpoints = [
            ['GET', '/api/school-classes'],
            ['GET', '/api/tasks'],
            ['POST', '/api/school-classes'],
            ['POST', '/api/tasks'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = match ($method) {
                'GET' => $this->getJson($url),
                'POST' => $this->postJson($url),
            };
            $response->assertStatus(401);
        }
    }
}
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
        $student2Token = $student2->createToken('test_token')->plainTextToken;

        $task = Task::factory()->create();
        $completion = \App\Models\TaskCompletion::factory()->create([
            'user_id' => $student1->id,
            'task_id' => $task->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $student2Token)
            ->getJson('/api/task_completions/' . $completion->id);

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_access_task_completion_outside_class(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);
        $teacherToken = $teacher->createToken('test_token')->plainTextToken;

        $task = Task::factory()->create();
        $completion = \App\Models\TaskCompletion::factory()->create([
            'user_id' => $student->id,
            'task_id' => $task->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $teacherToken)
            ->getJson('/api/task_completions/' . $completion->id);

        $response->assertStatus(403);
    }

    public function test_teacher_member_can_access_task_completion_in_class(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);
        $teacherToken = $teacher->createToken('test_token')->plainTextToken;

        $schoolClass = SchoolClass::factory()->create();
        $schoolClass->members()->attach($teacher->id);
        $schoolClass->members()->attach($student->id);

        $task = Task::factory()->create(['class_id' => $schoolClass->id]);
        $completion = \App\Models\TaskCompletion::factory()->create([
            'user_id' => $student->id,
            'task_id' => $task->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $teacherToken)
            ->getJson('/api/task_completions/' . $completion->id);

        $response->assertStatus(200);
    }

    public function test_teacher_index_lists_only_completions_from_member_classes(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);
        $otherStudent = User::factory()->create(['system_role' => 'student']);
        $teacherToken = $teacher->createToken('test_token')->plainTextToken;

        $memberClass = SchoolClass::factory()->create();
        $memberClass->members()->attach($teacher->id);
        $memberClass->members()->attach($student->id);

        $otherClass = SchoolClass::factory()->create();
        $otherClass->members()->attach($otherStudent->id);

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

        $response = $this->withHeader('Authorization', 'Bearer ' . $teacherToken)
            ->getJson('/api/task_completions');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'task_completions');
        $response->assertJsonFragment(['id' => $memberCompletion->id]);
    }

    public function test_user_cannot_modify_another_user_account(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user2Token = $user2->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $user2Token)
            ->putJson('/api/updateUser', [
                'name' => 'Hackeado',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user1->id, 'name' => $user1->name]);
        $this->assertDatabaseMissing('users', ['id' => $user1->id, 'name' => 'Hackeado']);
    }

    public function test_unauthenticated_cannot_access_any_resource(): void
    {
        $endpoints = [
            ['GET', '/api/school_classes'],
            ['GET', '/api/tasks'],
            ['POST', '/api/school_classes'],
            ['POST', '/api/tasks'],
        ];

        foreach ($endpoints as $endpoint) {
            $method = $endpoint[0];
            $url = $endpoint[1];

            $response = match ($method) {
                'GET' => $this->getJson($url),
                'POST' => $this->postJson($url),
            };
            $response->assertStatus(401);
        }
    }
}
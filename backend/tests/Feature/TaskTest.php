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

    public function test_teacher_can_create_task(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $token = $user->createToken('test_token')->plainTextToken;

        $schoolClass = SchoolClass::factory()->create();
        $schoolClass->members()->attach($user->id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/tasks', [
                'title' => 'Lista de Exercicios',
                'description' => 'Resolver exercicios do capitulo 5.',
                'priority' => 'high',
                'due_date' => '2026-12-31',
                'class_id' => $schoolClass->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => 'Lista de Exercicios']);
    }

    public function test_student_cannot_create_task(): void
    {
        $user = User::factory()->create(['system_role' => 'student']);
        $token = $user->createToken('test_token')->plainTextToken;

        $schoolClass = SchoolClass::factory()->create();
        $schoolClass->members()->attach($user->id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/tasks', [
                'title' => 'Lista de Exercicios',
                'class_id' => $schoolClass->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_class_member_can_view_task(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);
        $studentToken = $student->createToken('test_token')->plainTextToken;

        $schoolClass = SchoolClass::factory()->create();
        $schoolClass->members()->attach($teacher->id);
        $schoolClass->members()->attach($student->id);

        $task = Task::factory()->create([
            'class_id' => $schoolClass->id,
            'created_by' => $teacher->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $studentToken)
            ->getJson('/api/tasks/' . $task->id);

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_task_from_other_class(): void
    {
        $user = User::factory()->create(['system_role' => 'student']);
        $token = $user->createToken('test_token')->plainTextToken;

        $task = Task::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/tasks/' . $task->id);

        $response->assertStatus(403);
    }

    public function test_creator_can_update_own_task(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $token = $user->createToken('test_token')->plainTextToken;

        $schoolClass = SchoolClass::factory()->create();
        $schoolClass->members()->attach($user->id);

        $task = Task::factory()->create([
            'class_id' => $schoolClass->id,
            'created_by' => $user->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/tasks/' . $task->id, [
                'title' => 'Tarefa Atualizada',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Tarefa Atualizada',
        ]);
    }
}
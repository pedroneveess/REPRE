<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\SchoolClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_school_class(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);

        $response = $this->asUser($user)->postJson('/api/school-classes', [
            'name' => '3o ano A', 'course' => 'Ensino Medio', 'semester' => 2, 'year' => 2026,
            'starts_on' => '2026-02-01', 'ends_on' => '2026-12-15',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('school_classes', ['name' => '3o ano A']);
    }

    public function test_create_requires_dates(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);

        $response = $this->asUser($user)->postJson('/api/school-classes', [
            'name' => '3o ano A', 'course' => 'Medio', 'semester' => 2, 'year' => 2026,
        ]);

        $response->assertStatus(422);
    }

    public function test_create_rejects_end_before_start(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);

        $response = $this->asUser($user)->postJson('/api/school-classes', [
            'name' => 'A', 'course' => 'M', 'semester' => 1, 'year' => 2026,
            'starts_on' => '2026-12-01', 'ends_on' => '2026-01-01',
        ]);

        $response->assertStatus(422);
    }

    public function test_student_cannot_create_school_class(): void
    {
        $user = User::factory()->create(['system_role' => 'student']);

        $response = $this->asUser($user)->postJson('/api/school-classes', [
            'name' => 'A', 'course' => 'M', 'semester' => 1, 'year' => 2026,
            'starts_on' => '2026-02-01', 'ends_on' => '2026-12-15',
        ]);

        $response->assertStatus(403);
    }

    public function test_list_is_paginated_and_scoped(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        SchoolClass::factory()->count(2)->create()->each(fn ($c) => $c->members()->attach($user->id));

        $response = $this->asUser($user)->getJson('/api/school-classes');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $this->assertArrayHasKey('links', $response->json());
        $this->assertArrayHasKey('meta', $response->json());
    }

    public function test_update_partial_dates_are_validated(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $schoolClass = SchoolClass::factory()->create(['starts_on' => '2026-02-01', 'ends_on' => '2026-12-15']);
        $schoolClass->members()->attach($user->id, ['role' => 'teacher']);

        $this->asUser($user)->patchJson('/api/school-classes/'.$schoolClass->id, ['ends_on' => '2026-01-01'])
            ->assertStatus(422);

        $this->asUser($user)->patchJson('/api/school-classes/'.$schoolClass->id, ['ends_on' => '2026-12-20'])
            ->assertStatus(200);
    }

    public function test_teacher_can_delete_school_class(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $schoolClass = SchoolClass::factory()->create();
        $schoolClass->members()->attach($user->id, ['role' => 'teacher']);

        $this->asUser($user)->deleteJson('/api/school-classes/'.$schoolClass->id)->assertStatus(204);
        $this->assertDatabaseMissing('school_classes', ['id' => $schoolClass->id]);
    }

    public function test_user_cannot_access_other_class(): void
    {
        $user = User::factory()->create(['system_role' => 'student']);
        $schoolClass = SchoolClass::factory()->create();

        $this->asUser($user)->getJson('/api/school-classes/'.$schoolClass->id)->assertStatus(403);
    }

    public function test_invalid_uuid_returns_404(): void
    {
        $user = User::factory()->create(['system_role' => 'admin']);

        $this->asUser($user)->getJson('/api/school-classes/not-a-uuid')->assertStatus(404);
    }

    public function test_performance_counts_completions_and_zeros(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($teacher->id, ['role' => 'teacher']);

        $student = User::factory()->create(['system_role' => 'student']);
        $representative = User::factory()->create(['system_role' => 'representative']);
        $class->members()->attach([$student->id => ['role' => 'student'], $representative->id => ['role' => 'representative']]);
        $task = Task::factory()->create(['class_id' => $class->id]);
        TaskCompletion::factory()->create(['task_id' => $task->id, 'user_id' => $student->id]);
        $otherClass = SchoolClass::factory()->create();
        Task::factory()->count(3)->create(['class_id' => $otherClass->id]);

        $response = $this->asUser($teacher)->getJson('/api/school-classes/'.$class->id.'/performance');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $student->id, 'tasks_total' => 1, 'completions_total' => 1]);
        $response->assertJsonFragment(['id' => $representative->id, 'tasks_total' => 1, 'completions_total' => 0]);

        $outsider = User::factory()->create(['system_role' => 'teacher']);
        $this->asUser($outsider)->getJson('/api/school-classes/'.$class->id.'/performance')->assertForbidden();
    }

    public function test_performance_counts_overdue_and_summarizes_all_students_across_pages(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-12-01T15:00:00Z'));
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $class = SchoolClass::factory()->create();
        $student = User::factory()->create(['system_role' => 'student']);
        $representative = User::factory()->create(['system_role' => 'student']);
        $class->members()->attach([$teacher->id => ['role' => 'teacher'], $student->id => ['role' => 'student'], $representative->id => ['role' => 'representative']]);
        $overdue = Task::factory()->create(['class_id' => $class->id, 'due_date' => '2026-12-01T11:59:59-03:00']);
        Task::factory()->create(['class_id' => $class->id, 'due_date' => '2026-12-01T12:00:00-03:00']);
        Task::factory()->create(['class_id' => $class->id, 'due_date' => '2026-12-01T12:00:01-03:00']);
        Task::factory()->create(['due_date' => '2026-11-30T12:00:00-03:00']);
        TaskCompletion::factory()->create(['task_id' => $overdue->id, 'user_id' => $student->id]);
        TaskCompletion::factory()->create(['task_id' => $overdue->id, 'user_id' => $teacher->id]);
        $path = '/api/school-classes/'.$class->id.'/performance';
        $summary = ['students_total' => 2, 'tasks_total' => 6, 'completions_total' => 1, 'pending_total' => 5, 'overdue_total' => 1, 'percentage' => 16.67];
        $rows = [];
        foreach ([1, 2] as $page) {
            $response = $this->asUser($teacher)->getJson($path.'?per_page=1&page='.$page)->assertOk()
                ->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 2)
                ->assertJsonPath('summary', $summary)->assertJsonStructure(['data', 'links', 'meta', 'summary']);
            $rows[$response->json('data.0.id')] = $response->json('data.0');
        }
        $this->assertSame(0, $rows[$student->id]['overdue_total']);
        $this->assertSame(1, $rows[$representative->id]['overdue_total']);
        $this->assertSame(2, $rows[$student->id]['pending_total']);
        $this->assertSame(3, $rows[$representative->id]['pending_total']);
        $this->asUser(User::factory()->create(['system_role' => 'admin']))->getJson($path)->assertOk()->assertJsonPath('summary', $summary);
        foreach ([$student, $representative, User::factory()->create(['system_role' => 'coordinator'])] as $user) {
            $this->asUser($user)->getJson($path)->assertForbidden();
        }
    }

    public function test_performance_summary_is_zero_without_students_or_tasks(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $class = SchoolClass::factory()->create();
        $path = '/api/school-classes/'.$class->id.'/performance';
        $summary = ['students_total' => 0, 'tasks_total' => 0, 'completions_total' => 0, 'pending_total' => 0, 'overdue_total' => 0, 'percentage' => 0];
        $this->asUser($admin)->getJson($path)->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('summary', $summary);
        $class->members()->attach(User::factory()->create()->id, ['role' => 'student']);
        $summary['students_total'] = 1;
        $this->asUser($admin)->getJson($path)->assertOk()->assertJsonPath('summary', $summary)->assertJsonPath('data.0.overdue_total', 0);
    }

    public function test_teacher_with_student_pivot_cannot_view_performance(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($teacher->id, ['role' => 'student']);
        $this->asUser($teacher)->getJson('/api/school-classes/'.$class->id.'/performance')->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_combine_with_visibility_and_bearer_completion(): void
    {
        $class = SchoolClass::factory()->create();
        $other = SchoolClass::factory()->create();
        $task = Task::factory()->create(['class_id' => $class->id, 'title' => 'Entrega FINAL']);
        Task::factory()->create(['class_id' => $class->id, 'title' => 'Outra atividade']);
        Task::factory()->create(['class_id' => $other->id, 'title' => 'Entrega FINAL']);
        foreach (['student', 'representative', 'teacher'] as $role) {
            $user = User::factory()->create(['system_role' => $role]);
            $class->members()->attach($user->id, ['role' => $role]);
            $params = ['class_id' => $class->id, 'search' => 'final', 'completed' => 'false'];
            $this->asUser($user)->getJson('/api/tasks?'.http_build_query($params))
                ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $task->id);
            TaskCompletion::factory()->create(['task_id' => $task->id, 'user_id' => $user->id]);
            foreach (['true', '1'] as $completed) {
                $params['completed'] = $completed;
                $this->asUser($user)->getJson('/api/tasks?'.http_build_query($params))
                    ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $task->id);
            }
            foreach (['false', '0'] as $completed) {
                $params['completed'] = $completed;
                $this->asUser($user)->getJson('/api/tasks?'.http_build_query($params))
                    ->assertOk()->assertJsonCount(0, 'data');
            }
            $this->asUser($user)->getJson('/api/tasks?class_id='.$other->id.'&search=final')
                ->assertOk()->assertJsonCount(0, 'data');
        }
    }

    public function test_day_interval_includes_midnight_and_excludes_next_day_with_stable_order(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $late = Task::factory()->create(['due_date' => '2026-12-01T23:59:59-03:00']);
        $first = Task::factory()->create(['id' => '10000000-0000-4000-8000-000000000001', 'due_date' => '2026-12-01T00:00:00-03:00']);
        $second = Task::factory()->create(['id' => '10000000-0000-4000-8000-000000000002', 'due_date' => '2026-12-01T00:00:00-03:00']);
        Task::factory()->create(['due_date' => '2026-11-30T23:59:59-03:00']);
        Task::factory()->create(['due_date' => '2026-12-02T00:00:00-03:00']);
        $response = $this->asUser($admin)->getJson('/api/tasks?due_from=2026-12-01&due_to=2026-12-01')->assertOk();
        $this->assertSame([$first->id, $second->id, $late->id], array_column($response->json('data'), 'id'));
    }

    public function test_datetime_filters_preserve_offsets_and_reject_reversed_instants(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $task = Task::factory()->create(['due_date' => '2026-12-01T12:30:00-03:00']);
        $boundary = Task::factory()->create(['due_date' => '2026-12-01T13:00:00-03:00']);
        Task::factory()->create(['due_date' => '2026-12-01T11:59:59-03:00']);
        Task::factory()->create(['due_date' => '2026-12-01T13:00:01-03:00']);
        $query = http_build_query(['due_from' => '2026-12-01T15:00:00Z', 'due_to' => '2026-12-01T13:00:00-03:00']);
        $this->asUser($admin)->getJson('/api/tasks?'.$query)
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $task->id)
            ->assertJsonPath('data.1.id', $boundary->id);
        $query = http_build_query(['due_from' => '2026-12-01T16:00:00Z', 'due_to' => '2026-12-01T13:00:00-03:00']);
        $this->asUser($admin)->getJson('/api/tasks?'.$query)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $boundary->id);
        $query = http_build_query(['due_from' => '2026-12-01T12:00:00-03:00', 'due_to' => '2026-12-01T14:00:00Z']);
        $this->asUser($admin)->getJson('/api/tasks?'.$query)->assertUnprocessable()->assertJsonValidationErrors('due_to');
    }

    public function test_individual_filters_and_priority_combination(): void
    {
        $user = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($user->id, ['role' => 'student']);
        $first = Task::factory()->create(['class_id' => $class->id, 'title' => 'Primeira', 'priority' => 'high', 'due_date' => '2026-12-01 12:00:00']);
        $second = Task::factory()->create(['class_id' => $class->id, 'title' => 'Segunda', 'priority' => 'low', 'due_date' => '2026-12-02 12:00:00']);
        TaskCompletion::factory()->create(['task_id' => $first->id, 'user_id' => $user->id]);
        TaskCompletion::factory()->create(['task_id' => $second->id, 'user_id' => User::factory()->create()->id]);
        foreach (['class_id='.$class->id => [$first->id, $second->id], 'completed=true' => [$first->id],
            'completed=false' => [$second->id], 'due_from=2026-12-02' => [$second->id],
            'due_to=2026-12-01' => [$first->id], 'priority=high' => [$first->id],
            'priority=high&completed=false' => [], 'search=PRIMEIRA' => [$first->id]] as $query => $ids) {
            $response = $this->asUser($user)->getJson('/api/tasks?'.$query)->assertOk();
            $this->assertSame($ids, array_column($response->json('data'), 'id'));
        }
    }

    public function test_order_is_stable_across_pages_in_both_directions(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $first = Task::factory()->create(['id' => '10000000-0000-4000-8000-000000000001', 'due_date' => '2026-12-01 12:00:00']);
        $second = Task::factory()->create(['id' => '10000000-0000-4000-8000-000000000002', 'due_date' => '2026-12-01 12:00:00']);
        $last = Task::factory()->create(['due_date' => '2026-12-02 12:00:00']);
        foreach (['asc' => [$first->id, $second->id, $last->id], 'desc' => [$last->id, $second->id, $first->id]] as $order => $ids) {
            foreach ($ids as $page => $id) {
                $this->asUser($admin)->getJson('/api/tasks?order='.$order.'&per_page=1&page='.($page + 1))
                    ->assertOk()->assertJsonPath('data.0.id', $id)->assertJsonPath('meta.total', 3);
            }
        }
        foreach (['order' => 'invalid', 'priority' => 'urgent'] as $field => $value) {
            $this->asUser($admin)->getJson('/api/tasks?'.$field.'='.$value)->assertUnprocessable()->assertJsonValidationErrors($field);
        }
    }

    public function test_invalid_filters_return_field_errors_and_unknown_uuid_returns_empty(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        foreach (['class_id' => 'bad', 'completed' => 'yes', 'due_from' => 'invalid', 'due_to' => 'invalid', 'search' => str_repeat('a', 101)] as $key => $value) {
            $this->asUser($admin)->getJson('/api/tasks?'.http_build_query([$key => $value]))
                ->assertUnprocessable()->assertJsonValidationErrors($key);
        }
        $this->asUser($admin)->getJson('/api/tasks?class_id=10000000-0000-4000-8000-000000000099')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->asUser($admin)->getJson('/api/tasks?due_from=2026-12-02&due_to=2026-12-01')
            ->assertUnprocessable()->assertJsonValidationErrors('due_to');
    }
}

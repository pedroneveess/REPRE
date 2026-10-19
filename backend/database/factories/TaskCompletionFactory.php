<?php

namespace Database\Factories;

use App\Models\TaskCompletion;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskCompletionFactory extends Factory
{
    protected $model = TaskCompletion::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'task_id' => Task::factory(),
            'completed_at' => fake()->dateTimeBetween('now', '+30 days'),
        ];
    }
}

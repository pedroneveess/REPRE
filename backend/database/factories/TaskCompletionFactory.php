<?php

namespace Database\Factories;

use App\Models\TaskCompletion;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskCompletionFactory extends Factory
{
    protected $model = TaskCompletion::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'task_id' => null,
            'completed_at' => fake()->dateTimeBetween('now', '+30 days'),
        ];
    }
}

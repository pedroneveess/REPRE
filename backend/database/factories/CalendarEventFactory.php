<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-1 month', '+3 months');
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+1 hour'),
            'class_id' => SchoolClass::factory(),
            'created_by' => User::factory(),
        ];
    }

    public function global(): static
    {
        return $this->state(fn () => ['class_id' => null]);
    }
}

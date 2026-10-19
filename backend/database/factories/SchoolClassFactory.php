<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word() . ' ' . fake()->randomElement(['A', 'B', 'C']),
            'course' => fake()->randomElement(['Ensino Médio', 'Técnico em Informática', 'Administração']),
            'semester' => fake()->numberBetween(1, 8),
            'year' => fake()->numberBetween(2024, 2026),
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-12-15',
        ];
    }
}

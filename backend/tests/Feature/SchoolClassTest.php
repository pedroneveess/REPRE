<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SchoolClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_school_class(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/school_classes', [
                'name' => '3o ano A',
                'course' => 'Ensino Medio',
                'semester' => 2,
                'year' => 2026,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('school_classes', ['name' => '3o ano A']);
    }

    public function test_student_cannot_create_school_class(): void
    {
        $user = User::factory()->create(['system_role' => 'student']);
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/school_classes', [
                'name' => '3o ano A',
                'course' => 'Ensino Medio',
                'semester' => 2,
                'year' => 2026,
            ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_list_school_classes(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $token = $user->createToken('test_token')->plainTextToken;

        SchoolClass::factory()->count(3)->create()->each(
            fn ($schoolClass) => $schoolClass->members()->attach($user->id)
        );

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/school_classes');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'school_classes');
    }

    public function test_teacher_can_update_school_class(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $token = $user->createToken('test_token')->plainTextToken;

        $schoolClass = SchoolClass::factory()->create();
        $schoolClass->members()->attach($user->id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/school_classes/' . $schoolClass->id, [
                'name' => 'Nome Atualizado',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('school_classes', [
            'id' => $schoolClass->id,
            'name' => 'Nome Atualizado',
        ]);
    }

    public function test_teacher_can_delete_school_class(): void
    {
        $user = User::factory()->create(['system_role' => 'teacher']);
        $token = $user->createToken('test_token')->plainTextToken;

        $schoolClass = SchoolClass::factory()->create();
        $schoolClass->members()->attach($user->id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/school_classes/' . $schoolClass->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('school_classes', ['id' => $schoolClass->id]);
    }

    public function test_user_cannot_access_other_class(): void
    {
        $user = User::factory()->create(['system_role' => 'student']);
        $token = $user->createToken('test_token')->plainTextToken;

        $schoolClass = SchoolClass::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/school_classes/' . $schoolClass->id);

        $response->assertStatus(403);
    }
}
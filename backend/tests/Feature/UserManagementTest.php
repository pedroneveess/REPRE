<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsApi(User $user): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('feature')->plainTextToken);
    }

    public function test_teacher_who_creates_a_class_is_attached_as_teacher_member(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $response = $this->actingAsApi($teacher)->postJson('/api/school_classes', [
            'name' => 'Class A',
            'course' => 'Science',
            'semester' => 1,
            'year' => (int) date('Y'),
        ])->assertCreated();

        $this->assertDatabaseHas('class_members', [
            'class_id' => $response->json('school_class.id'),
            'user_id' => $teacher->id,
            'role' => 'teacher',
        ]);
        $student = User::factory()->create(['system_role' => 'student']);
        $this->actingAsApi($teacher)->postJson('/api/school_classes/'.$response->json('school_class.id').'/members', [
            'user_id' => $student->id,
            'role' => 'student',
        ])->assertCreated();
    }

    public function test_coordinator_reads_global_user_list_and_is_read_only(): void
    {
        $coordinator = User::factory()->create(['system_role' => 'coordinator']);
        $unrelated = User::factory()->create(['system_role' => 'student']);

        $this->actingAsApi($coordinator)->getJson('/api/users')->assertOk()->assertJsonFragment(['id' => $unrelated->id]);
        $this->actingAsApi($coordinator)->getJson('/api/users/'.$unrelated->id)->assertOk();
        $this->actingAsApi($coordinator)->postJson('/api/users', [])->assertForbidden();
        $this->actingAsApi($coordinator)->patchJson('/api/users/'.$unrelated->id, ['system_role' => 'admin'])->assertForbidden();
        $this->actingAsApi($coordinator)->deleteJson('/api/users/'.$unrelated->id)->assertForbidden();
    }

    public function test_admin_can_create_and_update_users_without_accepting_password_or_tokens(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $response = $this->actingAsApi($admin)->postJson('/api/users', [
            'name' => 'New Teacher',
            'email' => 'new-teacher@example.com',
            'birthdate' => '1995-02-03',
            'system_role' => 'teacher',
            'password' => 'submitted-password',
            'tokens' => [],
        ])->assertCreated();

        $id = $response->json('user.id');
        $created = User::findOrFail($id);
        $this->assertSame('teacher', $created->system_role);
        $this->assertNotSame('submitted-password', $created->password);
        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->actingAsApi($admin)->patchJson('/api/users/'.$id, ['system_role' => 'representative'])->assertOk();
        $this->assertDatabaseHas('users', ['id' => $id, 'system_role' => 'representative']);
    }

    public function test_regular_user_list_and_detail_are_limited_to_self_and_shared_scope(): void
    {
        $user = User::factory()->create(['system_role' => 'student']);
        $classmate = User::factory()->create(['system_role' => 'student']);
        $unrelated = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach([$user->id, $classmate->id]);

        $response = $this->actingAsApi($user)->getJson('/api/users')->assertOk();
        $response->assertJsonFragment(['id' => $user->id])->assertJsonFragment(['id' => $classmate->id])->assertJsonMissing(['id' => $unrelated->id]);
        $this->actingAsApi($user)->getJson('/api/users/'.$classmate->id)->assertOk();
        $this->actingAsApi($user)->getJson('/api/users/'.$unrelated->id)->assertForbidden();
        $this->actingAsApi($user)->patchJson('/api/users/'.$classmate->id, ['name' => 'Unauthorized'])->assertForbidden();
    }

    public function test_admin_delete_returns_conflict_for_restrict_references(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $creator = User::factory()->create(['system_role' => 'teacher']);
        $class = SchoolClass::factory()->create();
        Announcement::factory()->create(['class_id' => $class->id, 'created_by' => $creator->id]);

        $this->actingAsApi($admin)->deleteJson('/api/users/'.$creator->id)->assertStatus(409);
    }
}

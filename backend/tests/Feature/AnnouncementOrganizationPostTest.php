<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Organization;
use App\Models\Post;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementOrganizationPostTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsApi(User $user): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('feature')->plainTextToken);
    }

    public function test_coordinator_reads_all_resources_and_admin_can_manage_them(): void
    {
        $coordinator = User::factory()->create(['system_role' => 'coordinator']);
        $class = SchoolClass::factory()->create();
        $announcement = Announcement::factory()->create(['class_id' => $class->id]);
        $organization = Organization::factory()->create();
        $post = Post::factory()->create(['organization_id' => $organization->id]);

        $this->actingAsApi($coordinator)->getJson('/api/announcements')->assertOk()->assertJsonFragment(['id' => $announcement->id]);
        $this->actingAsApi($coordinator)->getJson('/api/organizations')->assertOk()->assertJsonFragment(['id' => $organization->id]);
        $this->actingAsApi($coordinator)->getJson('/api/posts')->assertOk()->assertJsonFragment(['id' => $post->id]);

        $admin = User::factory()->create(['system_role' => 'admin']);
        $this->actingAsApi($admin)->putJson('/api/announcements/'.$announcement->id, ['title' => 'Alterado'])->assertOk();
        $this->actingAsApi($admin)->putJson('/api/organizations/'.$organization->id, ['name' => 'Alterada'])->assertOk();
        $this->actingAsApi($admin)->putJson('/api/posts/'.$post->id, ['title' => 'Alterado'])->assertOk();
        $this->actingAsApi($admin)->deleteJson('/api/announcements/'.$announcement->id)->assertNoContent();
        $this->actingAsApi($admin)->deleteJson('/api/posts/'.$post->id)->assertNoContent();
        $this->actingAsApi($admin)->deleteJson('/api/organizations/'.$organization->id)->assertNoContent();
    }

    public function test_student_reads_only_resources_of_member_class_or_organization(): void
    {
        $student = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($student->id);
        $visibleAnnouncement = Announcement::factory()->create(['class_id' => $class->id]);
        $hiddenAnnouncement = Announcement::factory()->create();
        $organization = Organization::factory()->create();
        $organization->members()->attach($student->id);
        $visiblePost = Post::factory()->create(['organization_id' => $organization->id]);
        $hiddenPost = Post::factory()->create();
        $hiddenOrganization = Organization::factory()->create();

        $this->actingAsApi($student)->getJson('/api/announcements')->assertOk()->assertJsonFragment(['id' => $visibleAnnouncement->id])->assertJsonMissing(['id' => $hiddenAnnouncement->id]);
        $this->actingAsApi($student)->getJson('/api/posts')->assertOk()->assertJsonFragment(['id' => $visiblePost->id])->assertJsonMissing(['id' => $hiddenPost->id]);
        $this->actingAsApi($student)->getJson('/api/organizations')->assertOk()->assertJsonFragment(['id' => $organization->id])->assertJsonMissing(['id' => $hiddenOrganization->id]);
    }

    public function test_teacher_crud_is_limited_to_member_class_and_organization(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($teacher->id);
        $otherClass = SchoolClass::factory()->create();
        $announcement = Announcement::factory()->create(['class_id' => $otherClass->id]);
        $organization = Organization::factory()->create();
        $organization->members()->attach($teacher->id);
        $otherOrganization = Organization::factory()->create();
        $post = Post::factory()->create(['organization_id' => $otherOrganization->id]);

        $this->actingAsApi($teacher)->getJson('/api/announcements')->assertOk()->assertJsonMissing(['id' => $announcement->id]);
        $this->actingAsApi($teacher)->postJson('/api/announcements', ['title' => 'x', 'content' => 'y', 'class_id' => $otherClass->id])->assertForbidden();
        $this->actingAsApi($teacher)->putJson('/api/announcements/'.$announcement->id, ['title' => 'IDOR'])->assertForbidden();
        $this->actingAsApi($teacher)->postJson('/api/posts', ['title' => 'x', 'description' => 'y', 'organization_id' => $otherOrganization->id])->assertForbidden();
        $this->actingAsApi($teacher)->putJson('/api/posts/'.$post->id, ['title' => 'IDOR'])->assertForbidden();
        $this->actingAsApi($teacher)->postJson('/api/announcements', ['title' => 'Aviso', 'content' => 'Texto', 'class_id' => $class->id])->assertCreated();
        $this->actingAsApi($teacher)->postJson('/api/posts', ['title' => 'Post', 'description' => 'Texto', 'organization_id' => $organization->id])->assertCreated();
    }

    public function test_organization_creator_is_attached_as_administrator_and_student_cannot_create_resources(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $organization = $this->actingAsApi($teacher)->postJson('/api/organizations', ['name' => 'Org', 'description' => 'Descrição'])->assertCreated()->json('organization');
        $this->assertDatabaseHas('organization_members', ['organization_id' => $organization['id'], 'user_id' => $teacher->id, 'role' => 'administrator']);
        $student = User::factory()->create(['system_role' => 'student']);
        $this->actingAsApi($student)->postJson('/api/organizations', ['name' => 'Org', 'description' => 'Descrição'])->assertForbidden();
    }
}

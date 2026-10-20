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

    public function test_coordinator_reads_all_resources_and_admin_can_manage_them(): void
    {
        $coordinator = User::factory()->create(['system_role' => 'coordinator']);
        $class = SchoolClass::factory()->create();
        $announcement = Announcement::factory()->create(['class_id' => $class->id]);
        $organization = Organization::factory()->create();
        $post = Post::factory()->create(['organization_id' => $organization->id]);

        $this->asUser($coordinator)->getJson('/api/announcements')->assertOk()->assertJsonFragment(['id' => $announcement->id]);
        $this->asUser($coordinator)->getJson('/api/organizations')->assertOk()->assertJsonFragment(['id' => $organization->id]);
        $this->asUser($coordinator)->getJson('/api/posts')->assertOk()->assertJsonFragment(['id' => $post->id]);

        $admin = User::factory()->create(['system_role' => 'admin']);
        $this->asUser($admin)->putJson('/api/announcements/'.$announcement->id, ['title' => 'Alterado'])->assertOk();
        $this->asUser($admin)->putJson('/api/organizations/'.$organization->id, ['name' => 'Alterada'])->assertOk();
        $this->asUser($admin)->putJson('/api/posts/'.$post->id, ['title' => 'Alterado'])->assertOk();
        $this->asUser($admin)->deleteJson('/api/announcements/'.$announcement->id)->assertNoContent();
        $this->asUser($admin)->deleteJson('/api/posts/'.$post->id)->assertNoContent();
        $this->asUser($admin)->deleteJson('/api/organizations/'.$organization->id)->assertNoContent();
    }

    public function test_student_reads_only_resources_of_member_class_or_organization(): void
    {
        $student = User::factory()->create(['system_role' => 'student']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($student->id, ['role' => 'student']);
        $visibleAnnouncement = Announcement::factory()->create(['class_id' => $class->id]);
        $hiddenAnnouncement = Announcement::factory()->create();
        $organization = Organization::factory()->create();
        $organization->members()->attach($student->id, ['role' => 'member']);
        $visiblePost = Post::factory()->create(['organization_id' => $organization->id]);
        $hiddenPost = Post::factory()->create();
        $hiddenOrganization = Organization::factory()->create();

        $this->asUser($student)->getJson('/api/announcements')->assertOk()->assertJsonFragment(['id' => $visibleAnnouncement->id])->assertJsonMissing(['id' => $hiddenAnnouncement->id]);
        $this->asUser($student)->getJson('/api/posts')->assertOk()->assertJsonFragment(['id' => $visiblePost->id])->assertJsonMissing(['id' => $hiddenPost->id]);
        $this->asUser($student)->getJson('/api/organizations')->assertOk()->assertJsonFragment(['id' => $organization->id])->assertJsonMissing(['id' => $hiddenOrganization->id]);
    }

    public function test_teacher_crud_is_limited_to_member_class_and_organization(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $class = SchoolClass::factory()->create();
        $class->members()->attach($teacher->id, ['role' => 'teacher']);
        $otherClass = SchoolClass::factory()->create();
        $announcement = Announcement::factory()->create(['class_id' => $otherClass->id]);
        $organization = Organization::factory()->create();
        $organization->members()->attach($teacher->id, ['role' => 'teacher']);
        $otherOrganization = Organization::factory()->create();
        $post = Post::factory()->create(['organization_id' => $otherOrganization->id]);

        $this->asUser($teacher)->getJson('/api/announcements')->assertOk()->assertJsonMissing(['id' => $announcement->id]);
        $this->asUser($teacher)->postJson('/api/announcements', ['title' => 'x', 'content' => 'y', 'class_id' => $otherClass->id])->assertForbidden();
        $this->asUser($teacher)->putJson('/api/announcements/'.$announcement->id, ['title' => 'IDOR'])->assertForbidden();
        $this->asUser($teacher)->postJson('/api/posts', ['title' => 'x', 'description' => 'y', 'organization_id' => $otherOrganization->id])->assertForbidden();
        $this->asUser($teacher)->putJson('/api/posts/'.$post->id, ['title' => 'IDOR'])->assertForbidden();
        $this->asUser($teacher)->postJson('/api/announcements', ['title' => 'Aviso', 'content' => 'Texto', 'class_id' => $class->id])->assertCreated();
        $this->asUser($teacher)->postJson('/api/posts', ['title' => 'Post', 'description' => 'Texto', 'organization_id' => $organization->id])->assertCreated();
    }

    public function test_organization_creator_is_attached_as_teacher_and_student_cannot_create(): void
    {
        $teacher = User::factory()->create(['system_role' => 'teacher']);
        $organization = $this->asUser($teacher)->postJson('/api/organizations', ['name' => 'Org', 'description' => 'Descrição'])->assertCreated()->json('data');
        $this->assertDatabaseHas('organization_members', ['organization_id' => $organization['id'], 'user_id' => $teacher->id, 'role' => 'teacher']);
        $student = User::factory()->create(['system_role' => 'student']);
        $this->asUser($student)->postJson('/api/organizations', ['name' => 'Org', 'description' => 'Descrição'])->assertForbidden();
    }
}

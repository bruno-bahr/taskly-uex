<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\ProjectSidebar;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_project(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProjectSidebar::class)
            ->set('newProjectName', 'My First Project')
            ->call('createProject');

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'My First Project',
        ]);
    }

    public function test_creating_a_project_with_empty_name_fails_validation(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProjectSidebar::class)
            ->set('newProjectName', '')
            ->call('createProject')
            ->assertHasErrors(['newProjectName' => 'required']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_user_can_delete_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(ProjectSidebar::class)
            ->call('deleteProject', $project->id);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_user_cannot_view_another_users_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        Livewire::actingAs($intruder)
            ->test(ProjectSidebar::class)
            ->call('deleteProject', $project->id)
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_guest_cannot_access_a_project(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.show', $project))
            ->assertRedirect(route('login'));
    }
}
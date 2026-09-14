<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\TaskBoard;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_task_in_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(TaskBoard::class, ['project' => $project])
            ->set('title', 'Write tests')
            ->set('status', TaskStatus::InProgress->value)
            ->call('saveTask');

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Write tests',
            'status' => TaskStatus::InProgress->value,
        ]);
    }

    public function test_creating_a_task_with_empty_title_fails_validation(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(TaskBoard::class, ['project' => $project])
            ->set('title', '')
            ->call('saveTask')
            ->assertHasErrors(['title' => 'required']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_user_can_change_task_status(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::NotStarted,
        ]);

        Livewire::actingAs($user)
            ->test(TaskBoard::class, ['project' => $project])
            ->call('changeStatus', $task->id, TaskStatus::Completed->value);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::Completed->value,
        ]);
    }

    public function test_user_cannot_view_another_users_project_tasks(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_user_cannot_modify_a_task_in_another_users_project(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $this->assertFalse($intruder->can('update', $task));
        $this->assertTrue($owner->can('update', $task));
    }

    public function test_deleting_a_project_cascades_to_its_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $project->delete();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
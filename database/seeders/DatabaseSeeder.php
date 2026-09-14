<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@taskly.dev',
            'password' => bcrypt('password'),
        ]);

        $this->seedWebsiteRedesignProject($user);
        $this->seedGuitarLessonsProject($user);
        $this->seedEmptyProject($user);
    }

    private function seedWebsiteRedesignProject(User $user): void
    {
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Website redesign',
        ]);

        $design = $project->tags()->create(['name' => 'design']);
        $dev = $project->tags()->create(['name' => 'dev']);
        $ux = $project->tags()->create(['name' => 'ux']);

        $task1 = $project->tasks()->create([
            'title' => 'Redesign login screen',
            'short_description' => 'New UI proposal for authentication',
            'full_description' => "Explore a cleaner, single-card layout for login and registration.\n\nRemove default framework branding, apply the new visual identity.",
            'due_date' => now()->addDays(5),
            'status' => TaskStatus::InProgress,
        ]);
        $task1->tags()->attach([$design->id, $ux->id]);

        $task2 = $project->tasks()->create([
            'title' => 'Map onboarding journey',
            'short_description' => 'Document current flow and friction points',
            'full_description' => 'Walk through the current onboarding as a new user and note every point of confusion or drop-off risk.',
            'due_date' => now()->addDays(10),
            'status' => TaskStatus::NotStarted,
        ]);
        $task2->tags()->attach([$ux->id]);

        $task3 = $project->tasks()->create([
            'title' => 'Validate components with dev team',
            'short_description' => 'Align design tokens with the engineering team',
            'full_description' => 'Review spacing, color, and typography tokens against what is technically feasible to implement.',
            'due_date' => now()->subDays(2),
            'status' => TaskStatus::Completed,
        ]);
        $task3->tags()->attach([$design->id, $dev->id]);

        $task4 = $project->tasks()->create([
            'title' => 'Explore dark mode variant',
            'short_description' => 'Initial exploration, deprioritized',
            'full_description' => 'Was under consideration for this cycle but moved out of scope.',
            'due_date' => null,
            'status' => TaskStatus::Cancelled,
        ]);
        $task4->tags()->attach([$design->id]);
    }

    private function seedGuitarLessonsProject(User $user): void
    {
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Guitar lessons',
        ]);

        $theory = $project->tags()->create(['name' => 'theory']);
        $practice = $project->tags()->create(['name' => 'practice']);

        $task1 = $project->tasks()->create([
            'title' => 'Learn the 7 Greek modes',
            'short_description' => 'Major scale modes',
            'full_description' => 'Practice each mode over a backing track, starting with Ionian and Dorian.',
            'due_date' => now()->addDays(3),
            'status' => TaskStatus::InProgress,
        ]);
        $task1->tags()->attach([$theory->id, $practice->id]);

        $task2 = $project->tasks()->create([
            'title' => 'Practice barre chords',
            'short_description' => 'Focus on F and B major shapes',
            'full_description' => '15 minutes daily, focus on clean finger placement and buzz-free notes.',
            'due_date' => now()->addDays(1),
            'status' => TaskStatus::NotStarted,
        ]);
        $task2->tags()->attach([$practice->id]);
    }

    private function seedEmptyProject(User $user): void
    {
        Project::create([
            'user_id' => $user->id,
            'name' => 'App mobile',
        ]);
    }
}
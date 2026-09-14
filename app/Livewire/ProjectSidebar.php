<?php

namespace App\Livewire;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectSidebar extends Component
{
    public string $newProjectName = '';

    public ?int $editingProjectId = null;

    public string $editingName = '';

    public function createProject(): void
    {
        $this->validate([
            'newProjectName' => ['required', 'string', 'max:255'],
        ]);

        Auth::user()->projects()->create([
            'name' => $this->newProjectName,
        ]);

        $this->newProjectName = '';
    }

    public function startEditing(Project $project): void
    {
        $this->authorize('update', $project);

        $this->editingProjectId = $project->id;
        $this->editingName = $project->name;
    }

    public function saveRename(): void
    {
        $project = Project::findOrFail($this->editingProjectId);

        $this->authorize('update', $project);

        $this->validate([
            'editingName' => ['required', 'string', 'max:255'],
        ]);

        $project->update(['name' => $this->editingName]);

        $this->cancelEditing();
    }

    public function cancelEditing(): void
    {
        $this->editingProjectId = null;
        $this->editingName = '';
    }

    public function deleteProject(Project $project): void
    {
        $this->authorize('delete', $project);

        $project->delete();
    }

    public function render()
    {
        return view('livewire.project-sidebar', [
            'projects' => Auth::user()->projects()->latest()->get(),
        ]);
    }
}
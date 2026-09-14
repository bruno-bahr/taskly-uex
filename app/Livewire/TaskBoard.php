<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Livewire\Component;

class TaskBoard extends Component
{
    public Project $project;

    public string $viewMode = 'list'; // 'list' or 'kanban'

    // Form state (shared by create + edit modal)
    public bool $showForm = false;

    public ?int $editingTaskId = null;

    public string $title = '';

    public string $shortDescription = '';

    public string $fullDescription = '';

    public ?string $dueDate = null;

    public string $status = 'not_started';

    public array $tags = [];

    public string $newTag = '';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project;
    }

    public function toggleView(): void
    {
        $this->viewMode = $this->viewMode === 'list' ? 'kanban' : 'list';
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(Task $task): void
    {
        $this->authorize('update', $task);

        $this->editingTaskId = $task->id;
        $this->title = $task->title;
        $this->shortDescription = $task->short_description ?? '';
        $this->fullDescription = $task->full_description ?? '';
        $this->dueDate = $task->due_date?->format('Y-m-d\TH:i');
        $this->status = $task->status->value;
        $this->tags = $task->tags->pluck('name')->toArray();
        $this->showForm = true;
    }

    public function addTag(): void
    {
        $tag = trim($this->newTag);

        if ($tag !== '' && ! in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }

        $this->newTag = '';
    }

    public function removeTag(string $tag): void
    {
        $this->tags = array_values(array_filter($this->tags, fn ($t) => $t !== $tag));
    }

    public function saveTask(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'shortDescription' => ['nullable', 'string', 'max:255'],
            'fullDescription' => ['nullable', 'string'],
            'dueDate' => ['nullable', 'date'],
            'status' => ['required', 'in:' . implode(',', array_column(TaskStatus::cases(), 'value'))],
        ]);

        if ($this->editingTaskId) {
            $task = Task::findOrFail($this->editingTaskId);
            $this->authorize('update', $task);
        } else {
            $this->authorize('view', $this->project);
            $task = new Task(['project_id' => $this->project->id]);
        }

        $task->fill([
            'title' => $this->title,
            'short_description' => $this->shortDescription,
            'full_description' => $this->fullDescription,
            'due_date' => $this->dueDate,
            'status' => $this->status,
        ]);
        $task->project_id = $this->project->id;
        $task->save();

        $tagIds = collect($this->tags)->map(function (string $name) {
            return $this->project->tags()->firstOrCreate(['name' => $name])->id;
        });

        $task->tags()->sync($tagIds);

        $this->closeForm();
    }

    public function changeStatus(Task $task, string $status): void
    {
        $this->authorize('update', $task);

        $task->update(['status' => $status]);
    }

    public function deleteTask(Task $task): void
    {
        $this->authorize('delete', $task);

        $task->delete();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingTaskId = null;
        $this->title = '';
        $this->shortDescription = '';
        $this->fullDescription = '';
        $this->dueDate = null;
        $this->status = 'not_started';
        $this->tags = [];
        $this->newTag = '';
    }

    public function render()
    {
        return view('livewire.task-board', [
            'tasks' => $this->project->tasks()->with('tags')->latest()->get(),
            'statuses' => TaskStatus::cases(),
        ]);
    }
}
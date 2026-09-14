<?php

namespace App\Livewire;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Livewire\Component;
use Livewire\WithFileUploads;

class TaskBoard extends Component
{
    use WithFileUploads;

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

    public array $newAttachments = [];

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
            'newAttachments.*' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
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

        foreach ($this->newAttachments as $file) {
            $path = $file->store('attachments/' . $task->id, 'public');

            $task->attachments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->newAttachments = [];

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

        if ($this->editingTaskId === $task->id) {
            $this->closeForm();
        }

        $task->attachments->each->delete(); // triggers Observer, removing physical files
        $task->delete();
    }

    public function deleteAttachment(\App\Models\Attachment $attachment): void
    {
        $this->authorize('update', $attachment->task);

        $attachment->delete(); // triggers Observer, removes physical file
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
        $this->newAttachments = [];
    }

    public function render()
    {
        return view('livewire.task-board', [
            'tasks' => $this->project->tasks()->with(['tags', 'attachments'])->latest()->get(),
            'statuses' => TaskStatus::cases(),
        ]);
    }
}
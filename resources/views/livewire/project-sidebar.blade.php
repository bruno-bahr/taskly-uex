<div class="w-64 border-r border-gray-200 p-4 space-y-4">
    <h2 class="font-semibold text-gray-700">Projects</h2>

    <ul class="space-y-1">
        @foreach ($projects as $project)
            <li class="flex items-center justify-between group">
                @if ($editingProjectId === $project->id)
                    <form wire:submit="saveRename" class="flex-1 flex gap-1">
                        <input
                            type="text"
                            wire:model="editingName"
                            class="flex-1 text-sm border-gray-300 rounded"
                            autofocus
                        >
                        <button type="submit" class="text-xs text-green-600">✓</button>
                        <button type="button" wire:click="cancelEditing" class="text-xs text-gray-400">✕</button>
                    </form>
                @else
                    <a href="{{ route('projects.show', $project) }}"
                       class="text-sm text-gray-700 hover:text-indigo-600 flex-1">
                        📁 {{ $project->name }}
                    </a>
                    <div class="hidden group-hover:flex gap-2">
                        <button
                            wire:click="startEditing({{ $project->id }})"
                            title="Rename project"
                            class="text-xs text-gray-400 hover:text-indigo-500"
                        >
                            ✎
                        </button>
                        <button
                            wire:click="deleteProject({{ $project->id }})"
                            wire:confirm="Delete this project and all its tasks?"
                            title="Delete project"
                            class="text-xs text-gray-400 hover:text-red-500"
                        >
                            ✕
                        </button>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>

    <form wire:submit="createProject" class="flex gap-2">
        <input
            type="text"
            wire:model="newProjectName"
            placeholder="New project"
            class="flex-1 text-sm border-gray-300 rounded"
        >
        <button type="submit" class="text-sm text-indigo-600">+</button>
    </form>

    @error('newProjectName')
        <p class="text-xs text-red-500">{{ $message }}</p>
    @enderror
    @error('editingName')
        <p class="text-xs text-red-500">{{ $message }}</p>
    @enderror
</div>
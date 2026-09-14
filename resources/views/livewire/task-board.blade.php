<div class="flex-1 p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">{{ $project->name }}</h1>

        <div class="flex items-center gap-3">
            <button
                wire:click="toggleView"
                class="text-sm px-3 py-1.5 border border-gray-300 rounded hover:bg-gray-50"
            >
                {{ $viewMode === 'list' ? '⊞ Kanban' : '≡ List' }}
            </button>
            <button
                wire:click="openCreateForm"
                class="text-sm px-3 py-1.5 bg-indigo-600 text-white rounded hover:bg-indigo-700"
            >
                + New task
            </button>
        </div>
    </div>

    {{-- LIST VIEW --}}
    @if ($viewMode === 'list')
        <div class="space-y-2">
            @forelse ($tasks as $task)
                <div class="border border-gray-200 rounded p-3 flex items-start justify-between">
                    <div class="flex-1">
                        <p class="font-medium text-gray-800">{{ $task->title }}</p>
                        @if ($task->short_description)
                            <p class="text-sm text-gray-500">{{ $task->short_description }}</p>
                        @endif
                        <div class="flex items-center gap-2 mt-1">
                            @foreach ($task->tags as $tag)
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-2 ml-4">
                        @if ($task->due_date)
                            <span class="text-xs text-gray-400">🕐 {{ $task->due_date->format('d/m H:i') }}</span>
                        @endif

                        <select
                            wire:change="changeStatus({{ $task->id }}, $event.target.value)"
                            class="text-xs border-gray-300 rounded"
                        >
                            @foreach ($statuses as $statusOption)
                                <option value="{{ $statusOption->value }}" @selected($task->status === $statusOption)>
                                    {{ $statusOption->label() }}
                                </option>
                            @endforeach
                        </select>

                        <button wire:click="openEditForm({{ $task->id }})" title="Edit task" class="text-gray-400 hover:text-indigo-500">✎</button>
                        <button wire:click="deleteTask({{ $task->id }})" wire:confirm="Delete this task?" title="Delete task" class="text-gray-400 hover:text-red-500">✕</button>
                    </div>
                </div>
            @empty
                <p class="text-gray-400 text-sm">No tasks yet. Create the first one.</p>
            @endforelse
        </div>
    @endif

    {{-- KANBAN VIEW --}}
    @if ($viewMode === 'kanban')
        <div class="grid grid-cols-4 gap-4">
            @foreach ($statuses as $statusOption)
                <div class="bg-gray-50 rounded p-3">
                    <h3 class="text-sm font-semibold text-gray-600 mb-3">{{ $statusOption->label() }}</h3>

                    <div class="space-y-2">
                        @foreach ($tasks->where('status', $statusOption) as $task)
                            <div class="bg-white border border-gray-200 rounded p-2 shadow-sm">
                                <p class="text-sm font-medium text-gray-800">{{ $task->title }}</p>

                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach ($task->tags as $tag)
                                        <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">{{ $tag->name }}</span>
                                    @endforeach
                                </div>

                                @if ($task->due_date)
                                    <p class="text-xs text-gray-400 mt-1">🕐 {{ $task->due_date->format('d/m H:i') }}</p>
                                @endif

                                <div class="flex items-center justify-between mt-2">
                                    <button wire:click="openEditForm({{ $task->id }})" title="Edit task" class="text-xs text-gray-400 hover:text-indigo-500">✎ edit</button>
                                    <button wire:click="deleteTask({{ $task->id }})" wire:confirm="Delete this task?" title="Delete task" class="text-xs text-gray-400 hover:text-red-500">✕</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- CREATE/EDIT MODAL --}}
    @if ($showForm)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" wire:click.self="closeForm">
            <div class="bg-white rounded-lg p-6 w-full max-w-lg space-y-4">
                <h2 class="font-semibold text-lg">{{ $editingTaskId ? 'Edit task' : 'New task' }}</h2>

                <div>
                    <label class="text-sm text-gray-600">Title</label>
                    <input type="text" wire:model="title" class="w-full text-sm border-gray-300 rounded">
                    @error('title') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm text-gray-600">Short description</label>
                    <input type="text" wire:model="shortDescription" class="w-full text-sm border-gray-300 rounded">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Full description</label>
                    <textarea wire:model="fullDescription" rows="3" class="w-full text-sm border-gray-300 rounded"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm text-gray-600">Due date</label>
                        <input type="datetime-local" wire:model="dueDate" class="w-full text-sm border-gray-300 rounded">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Status</label>
                        <select wire:model="status" class="w-full text-sm border-gray-300 rounded">
                            @foreach ($statuses as $statusOption)
                                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-600">Tags</label>
                    <div class="flex flex-wrap gap-1 mb-2">
                        @foreach ($tags as $tag)
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded flex items-center gap-1">
                                {{ $tag }}
                                <button type="button" wire:click="removeTag('{{ $tag }}')" class="text-gray-400 hover:text-red-500">✕</button>
                            </span>
                        @endforeach
                    </div>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            wire:model="newTag"
                            wire:keydown.enter.prevent="addTag"
                            placeholder="Type and press Enter"
                            class="flex-1 text-sm border-gray-300 rounded"
                        >
                        <button type="button" wire:click="addTag" class="text-sm text-indigo-600">Add</button>
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-600">Attachments</label>

                    @if ($editingTaskId)
                        @php $existingTask = \App\Models\Task::find($editingTaskId); @endphp
                        <div class="space-y-1 mb-2">
                            @foreach ($existingTask->attachments as $attachment)
                                <div class="flex items-center justify-between text-xs bg-gray-50 px-2 py-1 rounded">
                                    <a href="{{ route('attachments.download', $attachment) }}" class="text-indigo-600 truncate" target="_blank">
                                        📎 {{ $attachment->original_name }}
                                    </a>
                                    <button type="button" wire:click="deleteAttachment({{ $attachment->id }})" wire:confirm="Delete this attachment?" class="text-gray-400 hover:text-red-500">✕</button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <input type="file" wire:model="newAttachments" multiple class="text-sm">
                    @error('newAttachments.*') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                    <div wire:loading wire:target="newAttachments" class="text-xs text-gray-400 mt-1">Uploading...</div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="closeForm" class="text-sm px-3 py-1.5 border border-gray-300 rounded">Cancel</button>
                    <button wire:click="saveTask" class="text-sm px-3 py-1.5 bg-indigo-600 text-white rounded">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
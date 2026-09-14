<x-app-layout>
    <div class="flex">
        @livewire('project-sidebar')
        @livewire('task-board', ['project' => $project])
    </div>
</x-app-layout>
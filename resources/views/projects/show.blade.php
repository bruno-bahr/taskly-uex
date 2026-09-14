<x-app-layout>
    <div class="flex">
        @livewire('project-sidebar')

        <div class="flex-1 p-6">
            <h1 class="text-xl font-semibold">{{ $project->name }}</h1>
            <p class="text-gray-500 mt-2">Tasks will appear here (Phase 3).</p>
        </div>
    </div>
</x-app-layout>

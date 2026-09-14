<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-xl font-medium text-ink">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-surface border border-line rounded-xl p-6">
                <livewire:profile.update-profile-information-form />
            </div>

            <div class="bg-surface border border-line rounded-xl p-6">
                <livewire:profile.update-password-form />
            </div>

            <div class="bg-surface border border-line rounded-xl p-6">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>
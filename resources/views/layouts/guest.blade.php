<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Taskly') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|fraunces:500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 bg-paper">
            <a href="/" wire:navigate class="mb-8">
                <span class="font-serif text-3xl font-medium text-ink">Taskly</span>
            </a>

            <div class="w-full sm:max-w-sm px-8 py-8 bg-surface border border-line rounded-xl shadow-sm">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
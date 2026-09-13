# Taskly — Technical Specification

## Overview
Taskly is a personal task management system. Users can register their own account,
organize their work into projects, and manage tasks within each project.

## Stack
- Laravel 11 + Livewire 3 + Alpine.js + Tailwind CSS
- MySQL (relational database)
- Redis (cache, sessions, queues)
- Docker (PHP-FPM + Nginx + MySQL + Redis + Node for asset build)

## Entities

### User
- id, name, email, password, timestamps

### Project
- id, user_id (FK), name, timestamps

### Task
- id, project_id (FK), title, short_description, full_description
- due_date (datetime), status (enum), timestamps

### Tag
- id, name (unique per user)

### task_tag (pivot table)
- task_id, tag_id

### Attachment
- id, task_id (FK), file_path, original_name, mime_type, timestamps

## Business Rules
- A user can only view/edit their own projects and tasks (enforced via Policies)
- Task status values: `not_started`, `in_progress`, `completed`, `cancelled`
- All task fields are editable after creation
- Tags are free-form (user types and creates on-the-fly, chip-style UI)

## Routes / Components
- `/login`, `/register` — authentication (Laravel Breeze)
- `/dashboard` — project list (sidebar)
- `/projects/{project}` — task board (List/Kanban toggle)
- Task CRUD handled via Livewire components (no separate REST routes — monolith architecture)

## Out of minimum scope (planned as stretch goals)
- Filter by tag/status
- Metrics dashboard (tasks by status/week)
- Drag-and-drop on Kanban board
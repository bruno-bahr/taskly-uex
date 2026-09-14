# Taskly — Technical Specification

## Overview
Taskly is a personal task management system. Users can register their own account,
organize their work into projects, and manage tasks within each project.

This is the high-level, original specification. Each development phase has
its own detailed spec, written before implementation and updated with
validation results after — see `docs/specs/`.

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
- id, project_id (FK), name (unique per project, not per user — tags are
  scoped to the project they were created in, reusable across that
  project's tasks)

### task_tag (pivot table)
- task_id, tag_id

### Attachment
- id, task_id (FK), file_path, original_name, mime_type, size, timestamps

## Business Rules
- A user can only view/edit their own projects and tasks (enforced via
  Policies, checked server-side — verified with two-account testing in
  every phase, not assumed from code alone)
- Task status values: `not_started`, `in_progress`, `completed`, `cancelled`
- All task fields are editable after creation
- Tags are free-form (user types and creates on-the-fly, chip-style UI)
- Attachment/task ownership is inherited through the parent chain
  (Attachment → Task → Project → User), not duplicated at every level

## Routes / Components
- `/login`, `/register` — authentication (Laravel Breeze)
- `/dashboard` — project list (sidebar)
- `/projects/{project}` — task board (List/Kanban toggle)
- `/attachments/{attachment}/download` — authenticated file download
- Task/Project CRUD handled via Livewire components (no separate REST
  routes — monolith architecture)

## Implemented beyond minimum scope
- Custom Docker setup (PHP-FPM/Nginx/MySQL/Redis/Node), including UID/GID
  alignment for a smoother local dev experience — not required by the
  challenge
- Redis as cache/session/queue driver (non-relational database differential)
- Automated test suite (37 tests, including cross-user authorization
  coverage) — see `tests/`
- Custom visual identity (replacing Breeze/Laravel defaults)
- Seeder with realistic demo data for immediate evaluation

## Documentation
- `docs/specs/` — one spec per phase, written before implementation
- `docs/ARCHITECTURE.md` — system architecture and data model
- `docs/SECURITY_DECISIONS.md` — documented risk-acceptance decisions
- `PROMPTS.md` — AI usage log

## Out of scope (not implemented)
- Filter by tag/status
- Metrics dashboard (tasks by status/week)
- CI/CD pipeline
- Cloud deployment
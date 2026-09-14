# Spec 03 — Tasks

## Goal
Allow a user to create and manage tasks within a project, with all fields
required by the challenge, a status workflow, and a toggle between List and
Kanban views. This is the core deliverable of the challenge.

## Scope
- [x] `tasks` migration: id, project_id (FK, cascade delete), title,
      short_description, full_description, due_date (datetime), status
      (enum), timestamps
- [x] `Task` model: `belongsTo(Project)`, `belongsToMany(Tag)` (via pivot),
      `hasMany(Attachment)` (attachments handled in Spec 05, but the
      relationship is defined here so `Task` is complete)
- [x] `TaskPolicy`: authorization derives from the parent project's owner
      (a task's "owner" is its project's owner — no direct `user_id` on
      `tasks`, to avoid duplicated/divergent ownership data)
- [x] Task CRUD: create, edit (all fields, including after creation), delete
- [x] Status field with 4 values: `not_started`, `in_progress`, `completed`,
      `cancelled` — user can change status at any time
- [x] List view: tasks in the active project shown as rows/cards, grouped or
      sortable by status
- [x] Kanban view: tasks shown in columns per status, matching the 4 status
      values above
- [x] Toggle button switching between List and Kanban (per project, not a
      single global app-wide preference — reflects the mock reference which
      shows the toggle scoped to a project's task board)
- [x] Tags: free-form, user types and creates on-the-fly (chip-style input),
      reusable across tasks within the same project scope
- [x] Due date: date + time input, displayed in a human-readable format

## Validation results
Manually verified end-to-end via browser on 2026-09-14:
- Created a task with title, both descriptions, due date, status, and
  multiple tags — all persisted correctly
- Task appears correctly in both List and Kanban views, in the right
  status column
- Editing a task re-opens the modal pre-filled with current values
  (including tags and formatted due date)
- Changing status via the List dropdown moves the task to the correct
  Kanban column on next toggle
- Delete with confirmation works from both views
- Authorization inherited correctly from the project owner (no separate
  `user_id` on tasks needed — verified via `TaskPolicy` traversing
  `task->project->user_id`)

## Technical decisions
- **Authorization inheritance**: `TaskPolicy` checks
  `$user->id === $task->project->user_id` rather than storing a redundant
  `user_id` on `tasks`. Rationale: a task's ownership is entirely defined
  by which project it belongs to; storing it twice risks the two values
  diverging (e.g. if a task were ever transferable between projects) and
  adds no query benefit at this scale.
- **View state (List/Kanban)**: kept as local Livewire component state
  (not persisted to the DB or session) for this phase — simplest option
  that satisfies the requirement ("user can toggle"). Persisting the
  preference is a candidate stretch-goal, not core scope.
- **Tags storage**: normalized (`tags` + `task_tag` pivot) rather than a
  comma-separated string column, so tags are queryable/filterable later
  (relevant for the "data analysis" requirement in the job posting) and to
  avoid duplicate/inconsistent tag spelling issues that a free-text column
  would allow.
- **Status as a PHP native enum** (backed by string values), not a plain
  string column with app-level validation only — gives static typing and a
  single source of truth for the 4 allowed values, reused in both the
  List and Kanban views.
- **Pivot table name**: explicitly declared as `task_tag` on both sides of
  the `belongsToMany` relationship. Eloquent's default convention would
  expect `tag_task` (alphabetical order of model names), which didn't match
  our migration's table name — declaring it explicitly avoids relying on
  a naming convention that's easy to get wrong when the table was created
  before the relationship code.

## Out of scope for this phase
- Attachments/photo uploads (Spec 05 — kept separate since file handling
  has distinct concerns: storage disk, validation, size limits)
- Drag-and-drop reordering within Kanban columns (stretch goal — see
  Spec 04 if implemented)
- Filtering by tag/status (stretch goal)
- Due-date reminders/notifications (not required by the challenge)

## Acceptance criteria
- A user can create a task with all required fields inside a project they own
- All fields (title, both descriptions, due date, tags) remain editable
  after the task is created — verified by editing each field independently
  and confirming persistence after reload
- Status can be changed to any of the 4 values at any time, from both the
  List and Kanban views
- Switching the List/Kanban toggle shows the same underlying tasks, just
  laid out differently — no data loss or divergence between views
- A user cannot view or modify a task belonging to a project they don't own,
  even via a forged task ID (verified with two accounts, same as Spec 02)
- Deleting a project deletes its tasks (cascade, verified at the DB level)

## AI usage notes
- Prompts used: see `PROMPTS.md` (entry: "phase-03-tasks")
- Manual review focus: confirm `TaskPolicy` correctly traverses
  `task->project->user_id` (not a shortcut that only checks route-level
  project binding, which could be bypassed if a task ID from a different
  project is submitted through a Livewire action payload)

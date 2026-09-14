# Spec 04 — Attachments

## Goal
Allow a user to attach files/photos to a task, completing the minimum
required task fields ("anexos e/ou fotos") from the challenge scope.

## Scope
- [x] `attachments` migration: id, task_id (FK, cascade delete), file_path,
      original_name, mime_type, size (bytes), timestamps
- [x] `Attachment` model: `belongsTo(Task)` (relationship already declared
      on `Task` in Phase 3)
- [x] `AttachmentPolicy` (or reuse `TaskPolicy` via the parent task —
      decided during implementation based on which is cleaner) — reused
      `TaskPolicy` via `$attachment->task`, no separate policy class needed
- [x] File upload UI inside the task create/edit modal (multiple files)
- [x] File type restriction: images (jpg, png, gif, webp) and common
      document types (pdf) — matches "anexos e/ou fotos" from the challenge
- [x] File size limit: 10 MB per file (`max:10240` in Livewire validation)
- [x] Download/view attachment from the task modal
- [x] Delete individual attachment
- [x] Attachments stored on the local filesystem via Laravel's `public`
      disk (`storage/app/public`, symlinked to `public/storage`)

## Validation results
Manually verified end-to-end via browser on 2026-09-14:
- Uploaded an image while editing a task; it saved and was downloadable,
  file integrity confirmed by opening it
- Deleted an individual attachment; confirmed it disappeared from the list
  without affecting the task or other attachments
- Deleted a task with an attachment; confirmed via `ls` on
  `storage/app/public/attachments/` that the physical file was actually
  removed from disk, not just the DB row (the concern flagged in this
  spec's original AI usage notes)
- Cross-user authorization: a second user attempting
  `GET /attachments/{id}/download` for an attachment they don't own via
  direct URL correctly received a 403

## Bug found & fixed during validation
Deleting a task while its edit modal was still open (`editingTaskId`
pointing at the just-deleted task) caused a `null` property access when
Livewire re-rendered the modal referencing the now-nonexistent task. Fixed
by having `deleteTask()` close the form first if the deleted task was the
one currently being edited. This wasn't caught by initial code review —
it surfaced from an actual user action sequence (delete while editing),
reinforcing the value of testing real interaction order, not just the
happy path.

## Technical decisions
- **Storage disk**: local `public` disk instead of S3/cloud storage.
  Rationale: cloud storage credentials are out of scope for a local/Docker
  challenge environment; the `public` disk is the standard Laravel
  approach and is explicitly swappable later (config-only change) if cloud
  storage were needed — noted as a possible "beyond minimum scope"
  enhancement if time allows.
- **Validation approach**: MIME type + extension both checked (not just
  file extension) via Laravel's `file` validation rule with `mimes:`,
  since relying on extension alone is a well-known validation weakness.
- **Authorization**: an attachment's ownership is inherited from its
  task (which inherits from its project), matching the same pattern
  established in Phase 3 for tasks — no redundant `user_id` stored on
  `attachments`.
- **Storage path structure**: `attachments/{task_id}/...` (files organized
  by task), avoiding filename collisions between uploads while keeping the
  original name recoverable for downloads via the `original_name` column.
- **Physical file cleanup**: DB-level cascade delete (foreign key) removes
  the `attachments` row when a task is deleted, but does NOT touch the
  physical file in `storage/app/public`. Solved via an `AttachmentObserver`
  hooked to the `deleting` Eloquent event, combined with explicitly calling
  `$task->attachments->each->delete()` in `TaskBoard::deleteTask()` before
  deleting the task itself — necessary because Eloquent model events don't
  fire on DB-level cascade deletes, only on explicit `->delete()` calls.
- **Download route**: authenticated route (`/attachments/{attachment}/download`)
  checking `auth()->id() === $attachment->task->project->user_id`, instead
  of serving files from a publicly guessable static path, so cross-user
  access is blocked at the application layer, not just by obscurity.

## Out of scope for this phase
- Image thumbnail generation/resizing (nice-to-have, not required)
- Virus/malware scanning of uploads (out of scope for this challenge's
  environment)
- Cloud storage (S3-compatible) — noted above as a possible stretch goal

## Acceptance criteria
- A user can upload one or more files while creating or editing a task
- Uploaded files are listed in the task modal with their original filename
- A user can download/open an uploaded file and it matches what was
  uploaded (integrity check, not just that a row exists)
- A user can delete an individual attachment without affecting the rest of
  the task
- Deleting a task deletes its attachments (both the DB rows and the actual
  files on disk — verified explicitly, since cascade delete alone only
  covers the DB row, not the physical file)
- A user cannot access another user's task attachments via a guessed/direct
  file URL — verified given the file is served through an authenticated
  route/policy check, not a publicly guessable static path alone

## AI usage notes
- Prompts used: see `PROMPTS.md` (entry: "phase-04-attachments")
- Manual review focus: confirm that deleting a task via cascade actually
  removes files from disk (a DB cascade does NOT delete files in
  `storage/app/public` automatically — this needed an explicit model
  event/observer, confirmed correct via manual `ls` inspection before and
  after deletion, not assumed from code alone)

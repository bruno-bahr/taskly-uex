# Spec 04 — Attachments

## Goal
Allow a user to attach files/photos to a task, completing the minimum
required task fields ("anexos e/ou fotos") from the challenge scope.

## Scope
- [ ] `attachments` migration: id, task_id (FK, cascade delete), file_path,
      original_name, mime_type, size (bytes), timestamps
- [ ] `Attachment` model: `belongsTo(Task)` (relationship already declared
      on `Task` in Phase 3)
- [ ] `AttachmentPolicy` (or reuse `TaskPolicy` via the parent task —
      decided during implementation based on which is cleaner)
- [ ] File upload UI inside the task create/edit modal (multiple files)
- [ ] File type restriction: images (jpg, png, gif, webp) and common
      document types (pdf) — matches "anexos e/ou fotos" from the challenge
- [ ] File size limit (defined during implementation, documented here once
      decided)
- [ ] Download/view attachment from the task modal
- [ ] Delete individual attachment
- [ ] Attachments stored on the local filesystem via Laravel's `public`
      disk (`storage/app/public`, symlinked to `public/storage`)

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
- **Storage path structure**: `attachments/{task_id}/{uuid}-{original_name}`
  to avoid filename collisions between uploads while keeping the original
  name recoverable for downloads.

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
  `storage/app/public` automatically — this needs an explicit model event
  or observer, and is a common oversight worth flagging if the AI-generated
  code relies on cascade alone)

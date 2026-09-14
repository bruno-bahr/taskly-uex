# Spec 02 — Projects

## Goal
Allow an authenticated user to create, view, rename, and organize multiple
projects. Projects are the top-level container tasks belong to (see Spec 03).

## Scope
- [x] `projects` migration: id, user_id (FK), name, timestamps
- [x] `Project` model with `belongsTo(User)` / `User hasMany(Project)`
- [x] `ProjectPolicy`: a user may only view/update/delete their own projects
- [x] Livewire component: project sidebar (list, create, select active project)
- [x] Create project (name only, required, max length validation)
- [x] Rename project (inline edit, with hover-revealed icon + native tooltip)
- [x] Delete project (with confirmation; cascades to its tasks — see below)
- [x] Dashboard route (`/dashboard`) shows the project list and, once a
      project is selected, forwards to `/projects/{project}`

## Validation results
Manually verified end-to-end via browser on 2026-09-13:
- Create, list, rename, and delete all work through the sidebar UI
- Rename: `startEditing` and `saveRename` both independently call
  `$this->authorize('update', $project)` — verified this isn't just a
  UI-level guard, since `saveRename` re-fetches the project by ID rather
  than trusting an injected model
- Cross-user authorization tested with two real accounts: second user
  attempting to access `/projects/1` (owned by the first user) via direct
  URL correctly received a 403, confirming the `can:view,project` route
  middleware works against Policy, not just route/UI visibility
- Unauthenticated access to `/projects/{id}` redirects to `/login` (auth
  middleware layer, checked before the Policy layer)

## Technical decisions
- **Cascade on delete**: deleting a project deletes its tasks (and their
  attachments) via a DB-level `onDelete('cascade')` foreign key, not an
  application-level loop. Rationale: guarantees referential integrity even
  if a task is deleted outside of Eloquent (e.g. a raw query, a future
  admin tool), and avoids N+1 deletes.
- **Authorization**: enforced via Laravel Policy (`ProjectPolicy::view`,
  `update`, `delete`), registered and checked in every Livewire component
  action — not just hidden in the UI. A user directly manipulating a
  Livewire request payload for a project they don't own must still be
  blocked server-side.
- No project-level sharing/collaboration in this phase (single-owner only,
  matching the challenge's "personal task management" framing).

## Out of scope for this phase
- Reordering projects (nice-to-have, not required by the challenge)
- Project archiving/soft-deletes (using hard delete for simplicity; can be
  revisited if time allows after core scope is done)
- Project-level metadata beyond `name` (e.g. color, description) — may be
  added later as a "beyond minimum scope" enhancement

## Acceptance criteria
- A logged-in user can create a project and immediately see it in the sidebar
- A user can rename a project and the change persists after reload
- A user can delete a project; its tasks are also removed (cascade verified
  at the DB level, not just hidden in the UI)
- User A cannot view, edit, or delete a project belonging to User B, even by
  directly guessing/forging a project ID (verified via Policy, tested with
  two separate accounts)
- Creating a project with an empty name is rejected with a validation error

## AI usage notes
- Prompts used: see `PROMPTS.md` (entry: "phase-02-projects")
- Manual review focus: confirm the cascade delete is defined in the
  migration (not simulated in a model event/observer), and manually test
  the cross-user authorization boundary with two accounts rather than
  trusting that the Policy alone "looks correct"

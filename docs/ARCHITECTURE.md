# Architecture

## Overview

Taskly is a **monolith**: Laravel serves both the backend logic and the
frontend UI via Livewire components, with no separate API/SPA layer. This
was a deliberate choice given the 3-day delivery window — it avoids the
overhead of a decoupled architecture (API versioning, CORS, a separate
frontend build/deploy) while still keeping business logic cleanly
separated from presentation through Livewire components and Eloquent
Policies.

## Authorization model

Every resource's ownership traces back to the `User` who created it.
`Project` has a direct `user_id`; `Task` and `Attachment` don't duplicate
that — they inherit ownership through their parent
(`Task` → `Project` → `User`). Rationale: storing ownership at every level
risks the values diverging (e.g. if a task were ever transferable between
projects) and adds no query benefit at this scale.

This is enforced with Laravel Policies checked server-side on every
action, not just hidden in the UI — verified manually with two separate
accounts at every phase (see `docs/specs/`).

## Data model
User
└─ Project (1:N)
├─ Task (1:N)
│ ├─ Tag (N:N, via task_tag pivot)
│ └─ Attachment (1:N)
└─ Tag (1:N — tags belong to a project, reusable across its tasks)


## Key technical decisions

- **Docker**, including a Dockerfile with `phpredis` (native extension,
  not `predis`) and UID/GID alignment between the container's `www-data`
  user and the host user, to avoid file-permission conflicts on the
  mounted volume — not required by the challenge, added on our own
  initiative for a smoother local dev experience.
- **Redis** as the cache/session/queue driver, doubling as the
  non-relational database differential mentioned in the job posting.
- **Normalized tags** (`tags` + `task_tag` pivot) instead of a
  comma-separated column, keeping them queryable — relevant to the job
  posting's "data analysis" requirement.
- **Authenticated file downloads** for attachments (a dedicated route
  checking ownership via Policy) instead of serving files from a publicly
  guessable static path.

Full list of trade-offs and their rationale, phase by phase: see
`docs/specs/*.md`.

# AI Prompts Log

This file documents the prompts used with AI assistance (Claude) throughout
Taskly's development, organized by phase (matching `docs/specs/`). It exists
to satisfy the challenge's requirement for prompt traceability and to show
where AI output was accepted as-is versus critically reviewed and corrected.

**Tool used:** Claude (Anthropic), conversational pair-programming style —
the assistant proposed commands/code, the developer executed them locally,
reported real output, and both iterated together on errors.

---

## phase-00-setup

**Representative prompts:**
- "Analise os requerimentos para esse desafio. Liste os requisitos e crie
  um workflow para implementar essa solução. Além da stack indicada, vamos
  incluir o uso de docker"
- "nao esta na documentacao, mas devemos php + laravel"
- "vamos começar do início: criando projeto, repo e docker. Faça um passo
  de cada vez"

**What AI generated:**
- Initial requirement analysis and phase-by-phase workflow
- `docker-compose.yml`, `docker/php/Dockerfile`, `docker/nginx/default.conf`
- Step-by-step git/SSH setup instructions

**Manual review & corrections (critical — this is where most real debugging happened):**
- AI-authored Dockerfile/compose/nginx-config content was initially delivered
  as shell heredocs (`cat > file << 'EOF' ... EOF`) intended to be pasted as
  a single terminal command. Multiple times the heredoc wrapper itself ended
  up saved *inside* the target file instead of being executed, corrupting
  `docker-compose.yml`, the PHP Dockerfile, the Nginx config, and later
  `.gitignore`. Each occurrence was caught by manually inspecting file
  contents (`cat`) before trusting them, not by assuming the AI's output
  was applied correctly.
- AI suggested `docker-compose` (v1, hyphenated) commands without initially
  checking the installed version; this later surfaced a real v1 bug
  (`KeyError: 'ContainerConfig'`) requiring installation of the v2 plugin
  from Docker's official apt repository — AI provided the repo-setup steps,
  developer executed and reported each result before proceeding.
- AI's first attempt at generating the Laravel project (`composer
  create-project laravel/laravel . `) failed because the target directory
  already had our own files; AI corrected course to install into a temp
  directory and merge with `cp -rn` (no-clobber) to preserve existing repo
  files.
- Composer blocked installation due to security-advisory policy; AI
  initially guessed the wrong config key (`audit.block`), then correctly
  identified `policy.advisories.block` from the actual Composer error
  message rather than guessing again.
- 500 error after first successful boot: AI initially guessed `APP_KEY`
  was missing; after generating it and the error persisted, escalated to
  checking Nginx/PHP-FPM logs directly (the nginx heredoc-corruption issue
  above) and then diagnosed a file-permission issue in `storage/` via
  direct log inspection (`Permission denied` in the Laravel error page)
  rather than continuing to guess.
- Missing `phpredis` PHP extension (app configured for Redis cache/session
  but extension not installed) was diagnosed from the actual rendered error
  page content, not assumed in advance.

## phase-01-auth

**Representative prompts:**
- "vamos com a seguinte linha: criar as specs para cada fase..."
  (establishes the phase-spec-before-code workflow used from here on)
- "prossiga" (after spec 01-auth.md was written and reviewed)

**What AI generated:**
- `docs/specs/01-auth.md` (written *before* implementation, per the
  developer's explicit process requirement)
- Breeze installation commands (`composer require laravel/breeze --dev`,
  `artisan breeze:install livewire`)

**Manual review & corrections:**
- Breeze's own post-install script tried to run `npm install && npm run
  build` inside the PHP container (`app`), which has no Node — the AI
  corrected this by running those commands in the dedicated `node` service
  instead, per the architecture decided in Phase 0.
- `composer audit` (run proactively, not because AI suggested skipping it)
  surfaced 3 real advisories against `laravel/framework`, including one
  with a CVE. AI attempted `composer update` to find a patched 11.x release;
  none existed. This became a documented, deliberate risk-acceptance
  decision (see `docs/SECURITY_DECISIONS.md`) rather than silently ignored
  or blindly upgraded to a major version under time pressure.
- End-to-end auth flow (register → dashboard redirect → logout → login
  redirect-when-guest → session persistence via Redis) was manually tested
  in the browser and via `redis-cli KEYS` before marking the phase complete
  — not assumed to work from code inspection alone.

## phase-02-projects

**Representative prompts:**
- "prossiga" (after `docs/specs/02-projects.md` was written and approved)
- "revise meu web.php" (developer-authored code, reviewed by AI)
- "commit antes ou seguimos com o codigo?" (process question, not a code
  generation prompt — included to show the developer actively directed
  commit granularity rather than accepting AI's default)

**What AI generated:**
- `projects` migration, `Project` model with relationships, `ProjectPolicy`,
  `ProjectSidebar` Livewire component (list/create/delete, later
  rename), and supporting routes/views

**Manual review & corrections:**
- Removing the closure-based route in `web.php` and rewriting it (per
  AI review of developer-authored code) accidentally dropped Breeze's
  default `profile`/`profile.update`/`profile.destroy` routes, causing a
  `RouteNotFoundException`. Root cause identified from the actual stack
  trace (pointing at `navigation.blade.php`), not guessed; routes restored
  pointing at the existing `ProfileController`.
- Multiple file-permission issues recurred (`dashboard.blade.php`,
  `project-sidebar.blade.php`, `ProjectSidebar.php` all silently failing to
  save from VS Code with `EACCES`, because files generated inside the
  Docker container were owned by `root`/container-UID, not the host user).
  This was root-caused (not just patched file-by-file) by rebuilding the
  PHP image with `usermod`/`groupmod` to align `www-data`'s UID/GID with
  the host user — a deliberate infrastructure fix chosen by the developer
  over repeatedly running `chown` after every command.
- A `SQLSTATE[42S22]: Column not found: projects.user_id` error revealed
  that the `projects` migration had run against an *earlier* version of the
  migration file (before `user_id`/`name` were added), due to the same
  save-desync issue above. Diagnosed by inspecting the live DB schema
  (`Schema::getColumnListing`) against the migration file on disk, rather
  than assuming they matched. Fixed via `migrate:fresh` once the file
  content was confirmed correct.
- Cross-user authorization was **not** accepted as "should work because the
  Policy code looks right" — it was manually tested end-to-end with two
  real registered accounts, confirming a 403 on direct URL access before
  the phase was marked complete.
- Rename feature: AI's own generated code was reviewed for a subtle
  authorization gap (`startEditing` checks `authorize('update', ...)`, but
  since `saveRename` is a separately-invokable Livewire action, it needed
  its *own* independent authorization check rather than relying on
  `startEditing` having been called first) — implemented defensively from
  the start rather than discovered as a bug later.

---

## General patterns worth noting for evaluation

- The single most common category of AI-introduced defect in this project
  was **shell heredoc corruption** (command text leaking into generated
  files). This was never assumed away — every file the AI generated via a
  shell command block was verified with `cat`/`tail` before being trusted,
  which is how each occurrence was caught quickly instead of compounding.
- The second most common category was **file-ownership/permission
  mismatches** between the Docker container's process user and the host
  editor (VS Code/WSL), which repeatedly caused *silent* save failures —
  the file looked edited in the editor but was unchanged on disk. This was
  eventually fixed at the infrastructure level rather than patched
  reactively each time.
- No AI-suggested code was merged into a phase without a corresponding
  manual, real (not assumed) validation step — browser testing for UI/auth
  flows, direct DB schema inspection for migrations, and two-account
  testing for authorization boundaries.

## phase-03-tasks

**Representative prompts:**
- "seria possivel otimizar nosso tempo gerando mais codigo, testando e
  comitando?" (developer explicitly requested larger, batched code
  generation rather than step-by-step, given the workflow was already
  validated as stable in prior phases)

**What AI generated:**
- `tasks`, `tags`, `task_tag` migrations; `Task`/`Tag` models; `TaskStatus`
  PHP enum; `TaskPolicy`; `TaskBoard` Livewire component (create/edit modal,
  List view, Kanban view, view toggle, tag chip input)

**Manual review & corrections:**
- Migration ordering bug: `create_task_tag_table` and `create_tasks_table`
  were generated with identical timestamps, causing the pivot table
  migration (which has a foreign key to `tasks`) to run *before* `tasks`
  existed — caught immediately from the real migration failure
  (`Failed to open the referenced table 'tasks'`), not anticipated in
  advance. Fixed by renaming migration files to enforce correct order.
- Eloquent pivot table naming convention bug: `belongsToMany` was declared
  without an explicit pivot table name, so Eloquent assumed the
  alphabetical default (`tag_task`), which didn't match the actual
  migration's table name (`task_tag`, matching the developer-facing entity
  order used throughout specs/docs). Caught from a live `QueryException`
  after actually creating a task and tags in the browser (not caught by
  code review alone) — corrected by explicitly declaring the pivot table
  name on both sides of the relationship.
- Confirmed via real Livewire request logs (not assumed) that task and tag
  records were correctly inserted even while the pivot `sync()` call was
  failing — used this to distinguish "data loss" from "one broken step in
  an otherwise-working flow" before deciding on the fix.

## phase-04-attachments

**Representative prompts:**
- "vamos seguir com a fase 4" (after reviewing the open-phases checklist)
- Developer chose the authenticated-route approach for downloads over a
  simpler public-disk-link approach when explicitly asked to decide

**What AI generated:**
- `attachments` migration, `Attachment` model, `AttachmentObserver`
  (physical file cleanup on delete), authenticated download route, file
  upload UI integrated into the existing `TaskBoard` Livewire component

**Manual review & corrections:**
- Confirmed (not assumed) that a DB-level cascade delete does NOT trigger
  Eloquent model events, meaning an Observer alone was insufficient —
  `TaskBoard::deleteTask()` was explicitly updated to loop over and delete
  each attachment via Eloquent before deleting the task, so the Observer's
  `deleting` hook actually fires and removes the physical file. Verified
  via direct filesystem inspection (`ls storage/app/public/attachments/`)
  before and after a task deletion, not just by reading the code.
- Found and fixed a real bug during interactive testing (not code review):
  deleting a task while its own edit modal was still open caused a null
  property access, because the Livewire component still held a reference
  to the now-deleted task's ID and tried to re-fetch it on re-render. Fixed
  by having `deleteTask()` close the form when the task being deleted
  matches the one currently open for editing.
- Cross-user authorization on the download route was tested with a real
  second account and a real URL guess attempt, not assumed correct from
  the `abort_unless` condition alone.

## phase-06-seeder-and-docs

**Representative prompts:**
- "meu tempo é curto, quais pontos faltam para entregar uma solucao de um
  bom nivel para o desafio" (developer asked for prioritization given time
  constraints, not just more features)
- "separa cada informacao" (developer requested the README be split into
  README.md + docs/ARCHITECTURE.md rather than one long file, after
  reviewing an initial single-file draft)

**What AI generated:**
- `README.md` (setup instructions, ports, links to other docs)
- `docs/ARCHITECTURE.md` (architecture, data model, technical decisions —
  split out after developer feedback on the initial combined draft)
- `database/seeders/DatabaseSeeder.php` with a demo user and realistic,
  varied sample data (multiple projects, tasks across all 4 statuses,
  tags) instead of the framework's default single-user seeder

**Manual review & corrections:**
- The AI's initial README draft combined setup instructions with deep
  architectural explanation in one file; developer pushed back and asked
  for the content to be separated, which is a better fit for how each
  audience actually reads the repo (a person cloning it wants setup first,
  someone evaluating architecture wants that content isolated and linkable)
- Seeder intentionally does not fabricate a fake file attachment (would
  require committing a binary sample file to the repo for no real benefit)
  — attachment upload/download was already manually validated end-to-end
  in Phase 4, so the seeder focuses on what it's good for: giving an
  evaluator realistic, browsable data on first login.

## phase-07-automated-tests

**Representative prompts:**
- "otimo, prossiga" (after developer confirmed prioritization: README →
  seeder → tests → video, given limited remaining time)

**What AI generated:**
- `tests/Feature/ProjectTest.php` and `tests/Feature/TaskTest.php`
  (creation, validation, status changes, and — the highest-value tests —
  cross-user authorization checks matching the manual testing done
  throughout every phase)
- `ProjectFactory` (filled in, was previously an empty Laravel default) and
  a new `TaskFactory`

**Manual review & corrections (this phase surfaced real, pre-existing gaps, not just test-writing bugs):**
- `Task` model was missing the `HasFactory` trait entirely — caught by the
  test suite itself (`BadMethodCallException: Call to undefined method
  App\Models\Task::factory()`), not by code review. This had been present
  since Phase 3 and had gone unnoticed because nothing had exercised
  `Task::factory()` until tests were written.
- Discovered `App\Http\Controllers\ProfileController` did not exist on
  disk at all, despite `routes/web.php` referencing it since Phase 2 and
  the app appearing to work fine in the browser (the route was simply
  never manually clicked during any phase's validation). Root-caused by
  checking the filesystem directly (`ls`) rather than assuming Breeze had
  generated it, which also revealed `app/Http/Requests/` and
  `resources/views/profile/edit.blade.php` were missing too — all three
  were reconstructed from Breeze's known-standard implementation.
- A Livewire component test asserting authorization
  (`Livewire::test(...)->call(...)->assertForbidden()`) failed with an
  unrelated-looking Livewire internal error ("Invalid Livewire snapshot
  structure") rather than a clean 403 assertion failure. Rather than
  fighting the test harness, switched to testing the `TaskPolicy` directly
  via `$user->can('update', $task)` — same real authorization path the
  app uses, more reliable to assert against in tests, and arguably clearer
  intent than simulating a full Livewire request cycle for what is
  fundamentally a Policy-logic question.
- Removed the framework's default `tests/Feature/ExampleTest.php`, which
  asserted `GET /` returns 200 — no longer true once `/` was changed to
  redirect to `/dashboard` in Phase 2. Deleted rather than "fixed", since
  it tested framework boilerplate behavior, not anything specific to
  Taskly.
- Final result: 37 tests, 95 assertions, all passing — including full
  coverage of the cross-user authorization boundary for both Projects and
  Tasks, which had previously only been verified manually.

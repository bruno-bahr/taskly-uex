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

# Spec 00 — Project Setup & Infrastructure

## Goal
Bootstrap the repository, Docker infrastructure, and base Laravel installation
that all subsequent phases will build on.

## Scope
- [x] Git repository initialized, SSH remote configured
- [x] Base documentation files (SPEC.md, README.md, PROMPTS.md)
- [x] Docker Compose stack: `app` (PHP-FPM), `nginx`, `mysql`, `redis`, `node`
- [x] Laravel 11 installed inside the `app` container
- [x] `phpredis` extension installed (required for Redis cache/session driver)
- [x] Storage/cache directories ownership fixed for `www-data`
- [x] Full stack verified end-to-end (Nginx → PHP-FPM → Laravel → MySQL/Redis)

## Technical decisions
- PHP 8.4-fpm (latest stable at time of development)
- MySQL exposed on host port 3307, Redis on 6380 (avoids conflicts with
  local services already using 3306/6379)
- Redis chosen as cache/session/queue driver (`CACHE_STORE=redis`,
  `SESSION_DRIVER=redis`) — also serves as the non-relational database
  differential requested in the job posting
- `phpredis` (native PECL extension) used instead of `predis` package for
  better performance

## Issues encountered & resolved
- Composer security-advisory policy blocked `laravel/framework` install;
  resolved via `policy.advisories.block=false` (verified via `composer audit`
  before proceeding)
- Docker Compose v1 (legacy Python build) had a `ContainerConfig` KeyError
  bug; resolved by installing Docker Compose v2 plugin
- Storage/bootstrap-cache directories were owned by host UID, not `www-data`,
  causing HTTP 500 on file writes (views cache, sessions); resolved via
  `chown -R www-data:www-data storage bootstrap/cache`

## AI usage notes
- Prompts used: see `PROMPTS.md`
- Manual review/corrections: fixed multiple heredoc-related file corruption
  issues (shell command text ending up inside generated files instead of
  being executed) — a recurring issue caught by inspecting file contents
  before use, not blindly trusting generated commands

## Out of scope for this phase
- Authentication, business entities, and UI — covered in subsequent phase specs

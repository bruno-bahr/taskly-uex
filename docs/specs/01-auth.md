# Spec 01 — Authentication

## Goal
Allow users to create their own account and log in, without relying on
third-party OAuth providers, as required by the challenge scope.

## Scope
- [ ] User registration (name, email, password)
- [ ] Login with email/password
- [ ] Logout
- [ ] Persistent session (server-side, via Redis session driver)
- [ ] Basic password validation rules (min length, confirmation field)
- [ ] Authenticated users are redirected to `/dashboard` (project list)
- [ ] Guests attempting to access protected routes are redirected to `/login`

## Technical decisions
- **Laravel Breeze (Livewire stack)** chosen as the auth scaffold. Rationale:
  - Ships idiomatic, well-tested auth flows (register/login/logout/password
    reset) without hand-rolling controllers that add no evaluative value
    to the challenge
  - Livewire stack matches the chosen monolith architecture (no separate
    SPA/API layer), keeping the 3-day scope realistic
  - No OAuth scaffolding is installed, matching the "no mandatory Google/
    Microsoft integration" requirement
- Session driver: Redis (already configured in Phase 0)
- Password hashing: Laravel default (bcrypt)

## Out of scope for this phase
- Email verification (not required by the challenge; can be mentioned as a
  possible improvement in the README)
- Password reset via email (Breeze scaffolds it, but SMTP is not configured
  for this challenge — will be noted as a known limitation, not disabled,
  since removing it adds risk of breaking Breeze's default routes)
- Rate limiting beyond Breeze's defaults
- Any authorization/Policy logic (covered in Phase 2 — Projects, once there
  is a resource to protect)

## Acceptance criteria
- A new user can register and is immediately logged in
- A registered user can log out and log back in with the same credentials
- Visiting `/dashboard` while logged out redirects to `/login`
- Refreshing the browser after login keeps the user authenticated (session
  persistence via Redis, not just in-memory/array driver)

## AI usage notes
- Prompts used: see `PROMPTS.md` (entry: "phase-01-auth")
- Manual review focus: verify Breeze-generated Livewire components use the
  `taskly_session` cookie name (custom, set in `.env`) and confirm Redis is
  actually storing session keys (not silently falling back to `file` driver)

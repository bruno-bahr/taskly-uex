# Security Decisions Log

This file tracks security-related decisions made during development that
required a risk trade-off, so the reasoning is traceable for review.

## 2026-09-13 — Accepted risk: Laravel 11.x security advisories

### Context
`composer audit` (and Composer's built-in advisory policy, which we disabled
via `policy.advisories.block=false` to unblock installation) reports 3
advisories affecting `laravel/framework`:

| Advisory | Severity | Summary |
|---|---|---|
| PKSA-m5cs-t1y6-qpcs | Medium | Temporary Signed URL Path Confusion |
| PKSA-3r5d-mb8f-1qw9 | High | CRLF injection in default email validation rule |
| PKSA-mdq4-51ck-6kdq (CVE-2026-48019) | — | Same CRLF injection, tracked with a CVE |

### Investigation
Ran `composer update laravel/framework --with-all-dependencies` to check for
a patched release within the `^11.0` constraint. Result: **every published
11.x release (v11.31.0 through the latest v11.56.1) is flagged** — no patched
11.x version exists yet. The fix appears to only be available starting in
the 12.x/13.x branches.

### Decision
**Stay on Laravel 11.56.1.** Do not upgrade to Laravel 12/13 within the scope
of this challenge.

### Rationale
- The CRLF injection affects the default `email` validation rule when its
  validated value is later used to compose raw email headers manually.
  Taskly does not send emails or construct email headers from user input
  anywhere in the application, so this specific attack vector is not
  reachable in this codebase.
- The signed-URL path confusion advisory affects temporary signed URLs;
  Taskly's attachment downloads do not currently rely on Laravel's signed
  URL feature (see Spec 05 — Attachments for the actual mechanism used).
- A major-version upgrade this late, under a 3-day delivery window, risks
  destabilizing the already-verified stack (Docker, Redis session/cache,
  Breeze auth) for a vulnerability class that isn't exploitable given how
  the application is built.
- This is a deliberate, documented risk acceptance — not an oversight.

### Follow-up (if this were a production system)
- Re-evaluate on every `composer audit` run
- Track Laravel 11.x patch releases; upgrade immediately if a fix lands
- If email-sending features are ever added, re-audit this decision before
  using the `email` validation rule near any raw header construction

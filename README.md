# Taskly

A personal task management web app. Create your own account, organize work
into projects, and manage tasks with status tracking, tags, attachments,
and both list and Kanban views.

Built for the UEX Fullstack Developer selection challenge.

## Stack

- **Backend/Frontend:** PHP 8.4, Laravel 11, Livewire 3, Alpine.js, Tailwind CSS
- **Database:** MySQL 8.0
- **Cache/session/queue:** Redis
- **Infrastructure:** Docker Compose (PHP-FPM + Nginx + MySQL + Redis + Node)

## Getting started

### Prerequisites

- Docker and Docker Compose v2 (`docker compose version` should work
  without a hyphen)

### Setup

```bash
# 1. Clone and enter the project
git clone git@github.com:bruno-bahr/taskly-uex.git
cd taskly-uex

# 2. Copy environment file
cp .env.example .env

# 3. Build and start all containers
docker compose build app
docker compose up -d

# 4. Install PHP dependencies (skipped if already in the image build)
docker compose exec app composer install

# 5. Generate the application key
docker compose exec app php artisan key:generate

# 6. Run migrations and seed sample data
docker compose exec app php artisan migrate --seed

# 7. Build frontend assets
docker compose exec node npm install
docker compose exec node npm run build

# 8. Link storage (for task attachments)
docker compose exec app php artisan storage:link
```

The app is available at **http://localhost:8000**.

### Sample login

The seeder creates a demo account so you can explore the app immediately
without registering:
Email: demo@taskly.dev
Password: password


### Services & ports

| Service | Purpose | Host port |
|---|---|---|
| `app` | PHP-FPM 8.4 | — (internal only) |
| `nginx` | Web server | `8000` |
| `mysql` | Database | `3307` (mapped to avoid conflicting with a local MySQL on 3306) |
| `redis` | Cache/session/queue | `6380` (mapped to avoid conflicting with a local Redis on 6379) |
| `node` | Asset build (Vite) | — (build-only, no long-running server) |

## Running tests

```bash
docker compose exec app php artisan test
```

## Documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — system architecture,
  data model, and key technical decisions
- [`docs/specs/`](docs/specs/) — one specification per development phase,
  written before implementation and updated with validation results
- [`docs/SECURITY_DECISIONS.md`](docs/SECURITY_DECISIONS.md) — documented
  risk-acceptance decisions
- [`PROMPTS.md`](PROMPTS.md) — AI usage log: prompts used, what the AI
  generated, and what was manually reviewed, tested, and corrected

## Project status

All minimum-scope requirements from the challenge are implemented:
authentication, projects, tasks (all required fields, status workflow,
list/Kanban toggle), and attachments.

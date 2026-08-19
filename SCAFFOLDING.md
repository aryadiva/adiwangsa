# Scaffolding Instructions

> **Prerequisites:** Docker + Docker Compose (for Laravel Sail), PHP 8.3+, Composer 2.x, Node.js 20+, Git.
> **Reference:** `prd-v2.md` (v3 PRD, v0.2.0 build) and `AGENTS.md` are the source of truth.
>
> **v0.2.0 note:** this file describes the original clean-scaffold flow (still valid for a fresh clone).
> The steps below don't change structurally for v0.2.0 — no new services, no new Composer/npm packages
> are currently expected (live-camera capture uses native browser APIs, not a new dependency; email
> delivery reuses the already-provisioned Mailpit service). If a Phase 8 task does turn out to need a
> new package, note it in the PR per `AGENTS.md` boundaries and add it here.

---

## Step 0: Clone & Configure

```bash
# 1. Clone the repository (or create a new Laravel project)
composer create-project laravel/laravel construction-ops
cd construction-ops

# 2. Copy environment file
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Update .env with your local credentials (PostgreSQL, Redis, MinIO/S3)
#    See .env.example for full reference.
```

---

## Step 1: Docker Environment (Laravel Sail)

```bash
# 1. Install Sail (if not already present)
php artisan sail:install --with=pgsql,redis,mailpit,minio

# 2. Start services
./vendor/bin/sail up -d

# 3. Alias sail for convenience (optional)
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

> **Services started:** PostgreSQL 15, Redis, Mailpit (email testing), MinIO (S3-compatible object storage).

---

## Step 2: Install Core Dependencies

```bash
# Run inside Sail container, or use the alias:
sail composer require filament/filament:"^3.0" -W
sail composer require spatie/laravel-permission
sail composer require spatie/laravel-activitylog
sail composer require spatie/laravel-pdf
sail composer require intervention/image-laravel

# Optional dev dependencies
sail composer require --dev laravel/pint larastan/larastan

# Install npm dependencies
sail npm install
```

---

## Step 3: Configure Filament & Shield

```bash
# 1. Install Filament panel
sail artisan filament:install --panels

# 2. Install Shield (Spatie Permissions wrapper for Filament)
sail artisan shield:install

# 3. Publish Shield config (if needed)
sail artisan vendor:publish --tag=shield-config
```

> **Post-install:** Review `config/filament-shield.php` and ensure `super_admin` role name matches your convention.

---

## Step 4: Database Setup

```bash
# 1. Run migrations (creates all tables with UUIDs, soft deletes, indexes)
sail artisan migrate

# 2. Run seeders (admin, site engineers, client, projects, sites, workers)
sail artisan db:seed

# Or fresh start:
sail artisan migrate:fresh --seed
```

> **Verify:** Check PostgreSQL that all tables from PRD §4 exist with correct columns, indexes, and FK constraints.

---

## Step 5: Storage & Queues

```bash
# 1. Create S3 bucket in MinIO (for local dev)
#    Visit http://localhost:8900 (MinIO Console), login with minioadmin/minioadmin,
#    and create a bucket named 'construction-ops'.

# 2. Link storage (if using local disk for anything, though S3 is preferred)
sail artisan storage:link

# 3. Start queue worker (required for PDF generation, notifications, image processing)
sail artisan queue:work --sleep=3 --tries=3 --timeout=90

# 4. (Optional) Start scheduler for recurring tasks
sail artisan schedule:work
```

---

## Step 6: Frontend Build

```bash
# Build assets for Filament (Tailwind, Alpine.js)
sail npm run build

# Or dev mode with HMR
sail npm run dev
```

---

## Step 7: Verification

```bash
# 1. Run the full test suite
sail artisan test
# or
sail pest

# 2. Run linter
sail pint

# 3. Run static analysis (if configured)
sail phpstan analyse
```

> **Expected:** All tests green, zero pint issues, zero phpstan errors at this stage (only base Laravel + Filament tests should exist before custom code).

---

## Step 8: Access the Application

| Service | URL | Credentials |
|--------|-----|-------------|
| App | http://localhost | See seeded users |
| Filament Admin (admin, site engineer, HRD) | http://localhost/admin | admin@example.com / password |
| Mailpit (email testing — also v0.2.0 client PDF report delivery) | http://localhost:8025 | — |
| MinIO Console | http://localhost:8900 | minioadmin / minioadmin |

> **v0.2.0:** the dedicated client Filament panel (`/client`) has been removed. Clients no longer
> log in; check Mailpit at the URL above to see emailed PDF reports during local development.
> **Default seeded users:** Check `database/seeders/UserSeeder.php` for exact credentials.

---

## Daily Development Workflow

```bash
# Start everything
sail up -d

# In terminal 1: queue worker
sail artisan queue:work

# In terminal 2: Vite dev server
sail npm run dev

# In terminal 3: your work
sail artisan make:model Project -mfsc
# ... edit, test, commit

# Before every commit
sail pint
sail pest

# Stop everything
sail down
```

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `SQLSTATE[08006] could not connect` | Ensure Sail is running: `sail up -d` |
| `Permission denied` on storage | `sail artisan cache:clear && sail artisan config:clear` |
| Queue jobs not processing | Ensure `QUEUE_CONNECTION=redis` and worker is running |
| Photos not uploading to MinIO | Verify `AWS_ENDPOINT` and bucket name in `.env`; check MinIO console |
| Filament Shield roles missing | Re-run `sail artisan shield:generate --all` and `sail artisan db:seed` |
| Tests failing on fresh clone | Run `sail artisan migrate:fresh --seed` first |

# PROD Deployment Guide

Construction Operations & Back-Office Management Dashboard — v0.2.0

Quick reference for taking this app live on a production VM using the **same Docker Compose stack** (self-baked `construction-ops/app:latest` image + PostgreSQL, Redis, MinIO), fronted by a **Caddy reverse proxy** for automatic HTTPS.

> **Boundary note (AGENTS.md):** switching Mailpit → real SMTP is the approved `.env`-only change. No application code changes are required for production email delivery.

---

## 1. Environment variables to change

Copy `.env.example` → `.env` on the production host (never commit `.env`). Change these lines:

### App

| Variable | Local | PROD | Why |
|---|---|---|---|
| `APP_ENV` | `local` | `production` | Enables production error handling |
| `APP_DEBUG` | `true` | `false` | Never expose stack traces |
| `APP_URL` | `http://localhost` | `https://ops.yourdomain.com` | Used in signed URLs and emails |
| `APP_KEY` | auto-generated on first boot | generate once, keep forever | Rotating invalidates sessions/encrypted values |

### Database & Redis

| Variable | Local | PROD | Why |
|---|---|---|---|
| `DB_USERNAME` | `sail` | dedicated user | Least-privilege DB account |
| `DB_PASSWORD` | `password` | strong random | — |
| `DB_HOST` | `pgsql` | `pgsql` (unchanged) | In-network compose hostname |
| `REDIS_PASSWORD` | `null` | strong random | Must match Redis `requirepass` (see §4) |
| `REDIS_HOST` | `redis` | `redis` (unchanged) | — |

### Email (approved Mailpit → SMTP swap)

| Variable | Local | PROD | Why |
|---|---|---|---|
| `MAIL_HOST` | `mailpit` | SMTP provider host | — |
| `MAIL_PORT` | `1025` | `587` (STARTTLS) or `465` (implicit TLS) | — |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | `null` | provider credentials | — |
| `MAIL_ENCRYPTION` | `null` | `tls` (587) or `ssl` (465) | — |
| `MAIL_FROM_ADDRESS` | `noreply@construction-ops.local` | `reports@yourdomain.com` | — |
| `REPORT_DEFAULT_SENDER_ADDRESS` | `reports@construction-ops.local` | `reports@yourdomain.com` | Client report email sender |

**Deliverability:** configure SPF, DKIM, and DMARC on `yourdomain.com` before go-live, or client report emails will land in spam.

### Storage (S3-compatible / MinIO)

| Variable | Local | PROD | Why |
|---|---|---|---|
| `AWS_ACCESS_KEY_ID` | `sail` | strong random | Also set `MINIO_ROOT_USER` in compose env to match |
| `AWS_SECRET_ACCESS_KEY` | `password` | strong random | Also set `MINIO_ROOT_PASSWORD` in compose env to match |
| `AWS_ENDPOINT` | `http://host.docker.internal:9000` | `https://s3.yourdomain.com` | One hostname for **both** the app SDK and user browsers |
| `AWS_URL` | `http://host.docker.internal:9000/construction-ops` | `https://s3.yourdomain.com/construction-ops` | Presigned URLs are signed against `AWS_ENDPOINT` |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` | `true` (unchanged) | MinIO path-style buckets |

### Logging & session

| Variable | Local | PROD | Why |
|---|---|---|---|
| `LOG_LEVEL` | `debug` | `warning` (or `info`) | — |
| `SESSION_SECURE_COOKIE` | — | `true` (add) | HTTPS-only cookies |

### Unchanged in production

`CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `FILESYSTEM_DISK`, `PDF_DRIVER=spatie`, `APP_LOCALE` / fallback, `MAX_PHOTO_SIZE_KB`, `SIGNED_URL_EXPIRY_MINUTES`, `REPORT_DEFAULT_CC`, `DEFAULT_DELAY_THRESHOLD_DAYS`, `PAYROLL_*`, `LIVE_CAPTURE_*`, `REDIS_CLIENT`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`.

---

## 2. Traffic path — reverse proxy

```
browser ──https──▶ Caddy (:80/:443, auto Let's Encrypt)
                    ├── ops.yourdomain.com  →  laravel.test:80
                    └── s3.yourdomain.com   →  minio:9000
```

- One Caddy service added to compose; certificates are automatic.
- **The `s3.` subdomain is mandatory.** `AWS_ENDPOINT` signs presigned URLs against a single hostname, and that hostname must be reachable by *both* the app containers (SDK API calls) and end-user browsers (photo previews, PDF downloads). Pointing them at different hosts breaks signing.
- MinIO stays as the storage backend — S3 API compatible, no migration needed.
- The app itself stays plain HTTP inside the Docker network; TLS terminates at Caddy.

---

## 3. Known production gaps (must address)

### Gap 1 — The scheduler never runs

`routes/console.php` schedules three nightly jobs **in a deliberate order**:

| Time | Command | Effect |
|---|---|---|
| 00:30 | `daily-targets:recompute` | Deficit carry-forward engine; recomputes `daily_target`, triggers persistent-shortfall warnings |
| 00:45 | `sub-job-delays:detect` | Creates Red delay events + cascades milestone/project date shifts |
| 01:15 | `payroll:generate` | Creates bi-weekly payroll run when the 14-day cycle closes |

No runner exists in the current stack. Without it: no target carry-forward, no delay cascade, no automatic payroll.

**Fix without code change** — host cron on the production VM:

```cron
* * * * * cd /srv/construction-ops && docker compose exec -T laravel.test php artisan schedule:run >> /var/log/construction-ops-scheduler.log 2>&1
```

Ordering is already correct in code (target recompute before delay detection, which runs before payroll generation — per AGENTS.md the recompute **must** precede the warning evaluation). Just make sure it runs.

*Cleaner long-term fix (proposed follow-up):* a dedicated `scheduler` compose service running `php artisan schedule:work`.

### Gap 2 — First boot seeds demo data

`docker/entrypoint.sh:85-89` runs `php artisan db:seed --force` whenever the `app-state` volume is fresh (no `.seeded` marker). On a brand-new production deploy this writes **demo users, sites, and records into the production database**.

Mitigations (pick one):
- Accept the seed, then immediately `docker compose exec laravel.test php artisan migrate:fresh --force` and create real records manually, or
- Pre-create the marker: `docker compose exec laravel.test mkdir -p storage/.docker && touch storage/.docker/.seeded` before first boot, or
- Approve a code change adding a `SEED_DEMO=false` env guard (proposed follow-up).

### Gap 3 — Seeded accounts have known passwords

Demo admin/engineer/HRD accounts ship with well-known passwords. Change every account password immediately after go-live (Administration > Users).

---

## 4. Hardening checklist

- [ ] Publish **only ports 80/443** to the internet. In a `compose.prod.yaml` override, bind `5432` (PostgreSQL), `6379` (Redis), `9000` (MinIO API), `8900` (MinIO console) to `127.0.0.1` or remove the mappings entirely.
- [ ] **Do not expose the Mailpit dashboard (`:8025`)** — dev-only tool; emails now go to real SMTP anyway.
- [ ] Set Redis `requirepass` to match `REDIS_PASSWORD`.
- [ ] TLS via Caddy; browsers must reach the app over HTTPS — **the live camera capture API requires a secure context**, so HTTPS is not optional for Site Engineer / HRD photo capture.
- [ ] Never commit `.env`. Keep real credentials out of `compose.yaml` defaults; use a production-only env file on the VM.
- [ ] Drop `GITHUB_TOKEN` from prod build args unless the VM hits composer rate limits during `--build`.

---

## 5. Deployment runbook

1. **Provision VM** — install Docker + Docker Compose plugin. Open `80/443`, firewall everything else.
2. **Get the code** — clone the repo to `/srv/construction-ops`.
3. **Create `.env`** from `.env.example` with the §1 table applied. `APP_KEY` is generated automatically on first boot if missing.
4. **Build and start:**
   ```bash
   docker compose up -d --build
   ```
   The web container's entrypoint will: wait for PostgreSQL and MinIO, run `migrate --force`, create the MinIO bucket, and (per Gap 2 — see mitigation) seed.
5. **Apply Gap 2 mitigation** (skip demo seed) if this is a truly fresh volume.
6. **Add Caddy proxy** for `ops.` and `s3.` hostnames; point DNS A/AAAA records at the VM.
7. **Install the scheduler cron** (§3 Gap 1).
8. **Verify end to end:**
   - Log in; check all six sidebar groups render per role.
   - Capture a progress photo (Site Engineer) and an attendance photo (HRD) — camera works over HTTPS.
   - Publish a daily report → PDF appears in Documents > Generated PDFs → client email arrives (check real inbox, not Mailpit).
   - `docker compose logs -f worker` shows queue jobs completing.
   - After 00:30 local, confirm `schedule:run` log shows the three commands ran.
9. **Rotate seeded passwords** (Gap 3).
10. **Set up backups** — nightly `pg_dump` plus MinIO data snapshot. Photos and PDFs are not regenerable; the database is.

---

## 6. Operations quick reference

| Task | Command |
|---|---|
| Deploy new build | `docker compose up -d --build` (migrations auto-run via entrypoint) |
| Run migrations manually | `docker compose exec laravel.test php artisan migrate --force` |
| Restart queue worker | `docker compose restart worker` |
| Cache config (optional perf) | `docker compose exec laravel.test php artisan config:cache` — **re-run after every `.env` change** |
| Watch logs | `docker compose logs -f laravel.test worker` |
| DB backup | `docker compose exec pgsql pg_dump -U <user> <db> > backup.sql` |
| Test suite before deploy | `docker compose exec laravel.test php artisan test` |

---

## 7. Proposed follow-ups (code changes, need approval)

| Item | Benefit |
|---|---|
| `SEED_DEMO=false` guard in `docker/entrypoint.sh` | Removes the demo-data-on-fresh-volume footgun (Gap 2) permanently |
| `scheduler` compose service (`php artisan schedule:work`) | Replaces the host-cron workaround (Gap 1) with an in-stack runner |
| `compose.prod.yaml` override file | Codifies port bindings/secret defaults instead of manual VM edits |

---

*Aligned with AGENTS.md conventions: `.env`-only email transport change (approved), no application code changes required for go-live.*
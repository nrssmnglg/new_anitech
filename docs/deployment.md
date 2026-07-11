# Deployment Guide

This repository is prepared for both Railway and Render. The app should be deployed as three Laravel services plus a database:

- web
- queue worker
- scheduler or cron
- database

## Shared production settings

Before deploying, set these environment variables for every Laravel service:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain`
- `APP_KEY=` output of `php artisan key:generate --show`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database` or `redis`
- `QUEUE_CONNECTION=database`
- `APP_MAINTENANCE_DRIVER=cache`
- `APP_MAINTENANCE_STORE=database`

Database values depend on your provider:

- Railway: use Railway MySQL or Postgres connection values
- Render: use external MySQL, or switch to Postgres if your schema and queries are compatible

If uploaded files and generated exports must persist across deploys, move `FILESYSTEM_DISK` to S3-compatible object storage. Local `storage/` is not durable enough for production on these platforms.

## Railway

Railway auto-detects Laravel and can run the web app without a Dockerfile.

### Services

Create these services in one Railway project:

- app service
- worker service
- cron service
- MySQL or Postgres service

### App service

- Source repo: this repository
- Custom build command: `npm run build`
- Pre-deploy command: `chmod +x ./railway/init-app.sh && ./railway/init-app.sh`
- Start command: leave Railway auto-detection enabled unless you need to override it

### Worker service

- Source repo: this repository
- Start command: `chmod +x ./railway/run-worker.sh && ./railway/run-worker.sh`

### Cron service

- Source repo: this repository
- Start command: `chmod +x ./railway/run-cron.sh && ./railway/run-cron.sh`

### Railway variables

Recommended minimum variables:

- `APP_KEY`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `SESSION_DRIVER`
- `CACHE_STORE`
- `QUEUE_CONNECTION`

If you provision the database inside Railway, map its connection values into the Laravel services from the Railway dashboard.

## Render

Render support in this repo uses Docker and the included [render.yaml](/abs/path/C:/xampp/htdocs/new_anitech_v2/render.yaml).

### Files added

- [Dockerfile](/abs/path/C:/xampp/htdocs/new_anitech_v2/Dockerfile)
- [render/start-web.sh](/abs/path/C:/xampp/htdocs/new_anitech_v2/render/start-web.sh)
- [render/nginx.conf](/abs/path/C:/xampp/htdocs/new_anitech_v2/render/nginx.conf)
- [render/supervisord.conf](/abs/path/C:/xampp/htdocs/new_anitech_v2/render/supervisord.conf)

### Render setup

Push the repo to GitHub, then create a new Blueprint in Render and point it to this repository. The blueprint creates:

- web service
- worker service
- cron service

The included `render.yaml` is set to `mysql` by default, which is safer for this codebase because the app already uses MySQL locally. Fill in:

- `APP_KEY`
- `APP_URL`
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Render does not provide a native MySQL service in the referenced Laravel guide, so use an external managed MySQL database unless you intentionally migrate the app to Postgres.

### Migrations on Render

The web container supports optional boot-time migrations through:

- `RUN_MIGRATIONS_ON_BOOT=true`

Safer practice is to keep that variable `false` and run:

```bash
php artisan migrate --force
```

as a one-off job during deployment.

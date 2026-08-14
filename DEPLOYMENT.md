# AniTech Production Deployment

This repository is a Laravel 12 application with a Vue 3/Inertia frontend. It is deployed as one Docker-based Render web service connected to Railway MySQL through Railway's public TCP proxy.

## Architecture and cost trade-offs

The free configuration runs Nginx, PHP-FPM, one Laravel queue worker, and the Laravel scheduler in the same Render web container. This avoids paid Render worker and cron services. A free Render service sleeps when idle, so queued work and scheduled commands are not guaranteed to execute until the web service wakes. Use dedicated paid worker and cron services when background processing must be timely.

Render's filesystem is ephemeral. Production uploads use the private S3-compatible bucket configured below when `FILESYSTEM_DISK=s3`; local development continues to use `storage/app/public`. Generated report exports and application backups still use Render-local storage and are not durable across restarts or deploys.

## Railway MySQL

1. Create a Railway project and choose the Singapore region when available.
2. Add a MySQL service.
3. Open the MySQL service and locate **Settings > Networking**.
4. Confirm the TCP proxy/public MySQL endpoint is enabled. Render cannot use a `railway.internal` address.
5. Copy the public proxy hostname, public proxy port, database name, username, and password. The public port might not be `3306`.
6. Do not commit any of these values.

Use one of these connection methods on Render:

### Connection URL

Set `DB_URL` to Railway's public MySQL URL. `MYSQL_PUBLIC_URL` is also supported as a fallback. Leave the unused URL variable empty.

### Individual variables

Set all of the following from Railway's public connection details:

```env
DB_CONNECTION=mysql
DB_HOST=<public TCP proxy hostname>
DB_PORT=<public TCP proxy port>
DB_DATABASE=<database name>
DB_USERNAME=<username>
DB_PASSWORD=<password>
```

## Render

### Blueprint deployment

1. Push this repository to GitHub or GitLab.
2. In Render, choose **New > Blueprint** and connect the repository.
3. Select the production branch.
4. Render reads `render.yaml` from the repository root.
5. Confirm the service uses the Singapore region, Docker runtime, Free plan, and `/up` health check.
6. Enter every secret marked `sync: false` during initial Blueprint creation.

Render configuration:

- Service type: Web Service
- Root directory: repository root
- Runtime: Docker
- Region: Singapore
- Build command: Docker image build from `./Dockerfile` (no dashboard build command)
- Start command: Docker `CMD`, `/usr/local/bin/start-web.sh`
- Health check: `/up`

### Required Render environment variables

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generated Laravel key>
APP_URL=https://<service-name>.onrender.com
LOG_CHANNEL=stderr
LOG_LEVEL=warning
DB_CONNECTION=mysql
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
APP_MAINTENANCE_DRIVER=cache
APP_MAINTENANCE_STORE=database
RUN_MIGRATIONS_ON_BOOT=true
```

Add either `DB_URL`/`MYSQL_PUBLIC_URL` or all five individual `DB_*` connection values described above. Optional features also require their matching variables:

- Password-reset email: `BREVO_API_KEY`, `BREVO_SENDER_NAME`, `BREVO_SENDER_EMAIL`
- PayMongo: `PAYMONGO_PUBLIC_KEY`, `PAYMONGO_SECRET_KEY`, `PAYMONGO_WEBHOOK_SECRET`, `PAYMONGO_QRPH_TEST_AMOUNT`
- SMTP instead of Brevo: the `MAIL_*` variables in `.env.example`
- S3-compatible storage: the `AWS_*` variables in `.env.example`

### Supabase Storage

Create a private Supabase Storage bucket and enable its S3 protocol connection. Configure Render with the server-side S3 credentials from **Storage > Settings > S3 Configuration**:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<Supabase S3 access key ID>
AWS_SECRET_ACCESS_KEY=<Supabase S3 secret access key>
AWS_DEFAULT_REGION=<region shown by Supabase>
AWS_BUCKET=anitech-private
AWS_ENDPOINT=https://<project-ref>.storage.supabase.co/storage/v1/s3
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Keep the bucket private. These credentials bypass Storage RLS and belong only in Render's secret environment settings. Existing files lost from Render cannot be recovered by enabling Supabase; upload them again after deployment.

Generate `APP_KEY` locally and copy the output to Render:

```bash
php artisan key:generate --show
```

Never change a production `APP_KEY` casually: doing so invalidates sessions and can make encrypted data unreadable.

## Database migrations and seed data

The schema is managed by Laravel migrations under `database/migrations`.

The Render Blueprint enables safe, idempotent migrations on container startup:

```bash
php artisan migrate --force --no-interaction
```

Startup never runs seeders. If production needs reference data, review `database/seeders` first and run the required seeder explicitly once. Never run `migrate:fresh`, `migrate:refresh`, `db:wipe`, or an unreviewed production seeder.

To disable boot-time migrations after initial deployment, set:

```env
RUN_MIGRATIONS_ON_BOOT=false
```

## Local production verification

Run these from the repository root:

```bash
composer validate
composer install
npm ci
php artisan test
npm run build
php artisan route:list
php artisan config:cache
docker build -t anitech-production .
```

Use a non-production MySQL database when testing migrations locally.

## After deployment

Verify the following without using production-only credentials in logs or screenshots:

1. Open `/up` and confirm HTTP 200.
2. Open `/login` and confirm styles and scripts load without console errors.
3. Sign in and sign out; verify the secure session cookie works.
4. Create a disposable test record and confirm it can be read and updated.
5. Delete that disposable record only if deletion is part of the normal application workflow.
6. Trigger one queue-backed action and check Render logs for successful processing.
7. Confirm scheduled commands appear in logs while the web service is awake.
8. Verify the Farmer PWA, manifest, service worker, images, and `/build` assets.
9. Test password-reset delivery if Brevo is configured.
10. Test PayMongo only with the intended environment and webhook secret.
11. Verify uploaded files using durable object storage before treating uploads as production-safe.

## Operational limitations of the free plan

- Cold starts are expected after idle periods.
- Queue workers and scheduled tasks stop when the web container sleeps.
- Application backups and generated report files remain local and are not durable. User uploads are durable only when the Supabase S3 variables are configured correctly.
- Railway public database traffic traverses the public TCP proxy; keep credentials secret and rotate them if exposed.

Upgrade to an always-on Render web instance plus dedicated worker/cron services when the application requires reliable background processing.

# GhanaBus Connect — Heroku Deployment Guide
# ============================================
# PHP 8.2+ | PostgreSQL (Heroku Postgres) | Apache
# Uses Heroku PHP buildpack: https://devcenter.heroku.com/articles/php-support

## Prerequisites

- [Heroku CLI](https://devcenter.heroku.com/articles/heroku-cli) installed
- A Heroku account (free tier available at heroku.com)
- Git repository with the `gbc/` directory as your Laravel project root


## ═══════════════════════════════════════
## STEP 1 — Create the Heroku App
## ═══════════════════════════════════════

```bash
# Log in to Heroku
heroku login

# Create a new app (choose a unique name, or let Heroku generate one)
heroku create your-app-name

# If your repo root is not the Laravel project, set the build root:
# (Run from the repo root — not required if composer.json is at repo root)
heroku config:set --app your-app-name \
  HEROKU_PHP_APP_ROOT=gbc
```

> **Note:** If your `composer.json` lives inside the `gbc/` sub-directory
> (as in this repo), push only that directory to Heroku, or configure the
> buildpack root — see Step 2.


## ═══════════════════════════════════════
## STEP 2 — Push the Laravel Sub-directory
## ═══════════════════════════════════════

Heroku expects `composer.json` and `Procfile` to be at the **root** of the
pushed repository.  Because this repo keeps the Laravel app in `gbc/`, use a
git subtree push:

```bash
# From the repository root
git subtree push --prefix gbc heroku main
```

This command pushes only the contents of `gbc/` to Heroku as if it were the
repository root.  Run this command every time you want to deploy.


## ═══════════════════════════════════════
## STEP 3 — Add the PostgreSQL Add-on
## ═══════════════════════════════════════

```bash
# Add Heroku Postgres (free hobby-dev tier)
heroku addons:create heroku-postgresql:essential-0

# Heroku automatically sets DATABASE_URL — verify it is present:
heroku config | grep DATABASE_URL
```

The app's `config/database.php` reads `DATABASE_URL` and automatically
selects the `pgsql` driver.  No manual `DB_*` variables are needed.

> **Prefer MySQL?**  Use the JawsDB add-on instead:
> ```bash
> heroku addons:create jawsdb:kitefin   # free tier
> heroku config | grep JAWSDB_URL
> # Copy that value and set it:
> heroku config:set DATABASE_URL=$(heroku config:get JAWSDB_URL)
> ```


## ═══════════════════════════════════════
## STEP 4 — Set Required Config Variables
## ═══════════════════════════════════════

```bash
# Generate a fresh APP_KEY locally and copy its value:
php artisan key:generate --show

# Set all required variables at once:
heroku config:set \
  APP_NAME="GhanaBus Connect" \
  APP_ENV=production \
  APP_DEBUG=false \
  APP_KEY="base64:PASTE_YOUR_GENERATED_KEY_HERE" \
  APP_URL=https://your-app-name.herokuapp.com \
  APP_TIMEZONE=Africa/Accra \
  LOG_CHANNEL=errorlog \
  SESSION_DRIVER=database \
  CACHE_STORE=database \
  QUEUE_CONNECTION=database \
  FILESYSTEM_DISK=local
```

> **Why `SESSION_DRIVER=database`?**  Heroku dynos have an *ephemeral*
> filesystem — files written there are lost on restart.  Database-backed
> sessions, cache, and queues survive dyno restarts.


## ═══════════════════════════════════════
## STEP 5 — Set Optional Service Keys
## ═══════════════════════════════════════

```bash
heroku config:set \
  PAYSTACK_PUBLIC_KEY=pk_live_xxxx \
  PAYSTACK_SECRET_KEY=sk_live_xxxx \
  PAYSTACK_PAYMENT_URL=https://api.paystack.co \
  PAYSTACK_MERCHANT_EMAIL=payments@yourdomain.com

# Mail (example using Mailgun)
heroku config:set \
  MAIL_MAILER=smtp \
  MAIL_HOST=smtp.mailgun.org \
  MAIL_PORT=587 \
  MAIL_USERNAME=postmaster@mg.yourdomain.com \
  MAIL_PASSWORD=your_mailgun_smtp_password \
  MAIL_ENCRYPTION=tls \
  MAIL_FROM_ADDRESS=noreply@yourdomain.com \
  MAIL_FROM_NAME="GhanaBus Connect"

# AI features (optional — system works without these)
heroku config:set AI_PROVIDER=mock
# heroku config:set OPENAI_API_KEY=sk-xxxx AI_PROVIDER=openai
```


## ═══════════════════════════════════════
## STEP 6 — Deploy
## ═══════════════════════════════════════

```bash
# From the repo root, push the gbc/ sub-directory to Heroku:
git subtree push --prefix gbc heroku main
```

Heroku will automatically:
1. Detect the PHP buildpack (via `composer.json`)
2. Run `composer install --no-dev --optimize-autoloader`
3. Start Apache with the document root pointing to `public/` (via `Procfile`)


## ═══════════════════════════════════════
## STEP 7 — Run Post-Deploy Commands
## ═══════════════════════════════════════

```bash
# Open a one-off dyno and run Laravel setup:
heroku run bash

# Inside the one-off dyno:
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
exit
```

> **Storage note:** `php artisan storage:link` creates a symlink inside the
> dyno's ephemeral filesystem.  Uploaded files (profile photos, QR codes)
> **will not persist** across dyno restarts unless you configure an S3-
> compatible disk.  See the *File Storage* section below.


## ═══════════════════════════════════════
## STEP 8 — Verify the App
## ═══════════════════════════════════════

```bash
heroku open
heroku logs --tail      # watch live logs
```

Health check endpoint: `https://your-app-name.herokuapp.com/up`


## ═══════════════════════════════════════
## STEP 9 — Scheduler (Cron Jobs)
## ═══════════════════════════════════════

The `Procfile` already includes a `worker` dyno for queue processing.
For scheduled tasks (seat-lock release, trip-status updates, etc.) add the
free Heroku Scheduler add-on:

```bash
heroku addons:create scheduler:standard
heroku addons:open scheduler
```

In the Scheduler dashboard add one job:
- **Command:** `php artisan schedule:run`
- **Frequency:** Every 10 minutes


## ═══════════════════════════════════════
## STEP 10 — Paystack Webhook
## ═══════════════════════════════════════

In your Paystack Dashboard → Settings → Webhooks, add:

```
https://your-app-name.herokuapp.com/webhooks/paystack
```

Events to enable: `charge.success`, `transfer.success`, `refund.processed`


## ═══════════════════════════════════════
## FILE STORAGE (Persistent Uploads)
## ═══════════════════════════════════════

Heroku's dyno filesystem is ephemeral.  To persist uploaded files (QR ticket
images, profile photos) configure an S3-compatible object store:

```bash
# Install AWS S3 driver
composer require league/flysystem-aws-s3-v3

# Set S3 config vars on Heroku
heroku config:set \
  FILESYSTEM_DISK=s3 \
  AWS_ACCESS_KEY_ID=your_key \
  AWS_SECRET_ACCESS_KEY=your_secret \
  AWS_DEFAULT_REGION=us-east-1 \
  AWS_BUCKET=your-bucket-name \
  AWS_URL=https://your-bucket-name.s3.amazonaws.com
```

For a quick alternative without AWS, use [Cloudinary](https://cloudinary.com)
or [Backblaze B2](https://www.backblaze.com/b2/cloud-storage.html) (both have
free tiers and S3-compatible APIs).


## ═══════════════════════════════════════
## TROUBLESHOOTING
## ═══════════════════════════════════════

| Symptom | Fix |
|---------|-----|
| 500 on every page | `heroku logs --tail` — likely missing `APP_KEY` |
| DB connection error | Verify `DATABASE_URL` is set: `heroku config \| grep DATABASE` |
| "Class not found" | `heroku run php artisan config:clear` then re-deploy |
| Sessions lost on reload | Set `SESSION_DRIVER=database` |
| Uploaded files 404 | Use S3 disk (see *File Storage* above) |
| Scheduler not running | Confirm Heroku Scheduler add-on is attached and job is saved |
| Queue jobs not processed | Scale the worker dyno: `heroku ps:scale worker=1` |


## ═══════════════════════════════════════
## DEMO CREDENTIALS (after seeding)
## ═══════════════════════════════════════

| Role       | Email                            | Password      |
|------------|----------------------------------|---------------|
| Admin      | admin@ghanabusconnect.com        | Admin@123!    |
| Operator   | vanef@ghanabusconnect.com        | Operator@123  |
| Operator   | oatravel@ghanabusconnect.com     | Operator@123  |
| Conductor  | conductor@ghanabusconnect.com    | Conduct@123   |
| Passenger  | kofi@example.com                 | Pass@1234     |

> **Security:** Change all default passwords immediately after first login.

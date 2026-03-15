# GhanaBus Connect

A Laravel 11 Progressive Web App (PWA) for bus ticket booking in Ghana.

## Tech Stack

- **Backend:** PHP 8.2+ / Laravel 11
- **Frontend:** Blade templates (no Node.js build step required)
- **Database:** MySQL 8+ (or ClearDB/JawsDB on Heroku)
- **Payments:** Paystack (GHS / Ghana Cedis)
- **PWA:** Service Worker + Web App Manifest

---

## Heroku Deployment

### Prerequisites

- [Heroku CLI](https://devcenter.heroku.com/articles/heroku-cli) installed and logged in
- A Heroku account

### 1. Create the Heroku app

```bash
heroku create your-app-name
```

### 2. Add a MySQL database

Heroku Postgres is the default add-on, but this app uses **MySQL**. Use ClearDB or JawsDB:

```bash
# ClearDB MySQL (free tier available)
heroku addons:create cleardb:ignite

# The add-on sets CLEARDB_DATABASE_URL automatically.
# Parse it into individual DB_* vars:
heroku config:set DB_URL=$(heroku config:get CLEARDB_DATABASE_URL)
```

Or provision JawsDB:

```bash
heroku addons:create jawsdb:kitefin
heroku config:set DB_URL=$(heroku config:get JAWSDB_URL)
```

### 3. Set required environment variables

```bash
# Generate an app key locally and set it
php artisan key:generate --show
# Then:
heroku config:set APP_KEY="base64:your-generated-key-here"

# App settings
heroku config:set APP_NAME="GhanaBus Connect"
heroku config:set APP_ENV=production
heroku config:set APP_DEBUG=false
heroku config:set APP_URL=https://your-app-name.herokuapp.com
heroku config:set APP_TIMEZONE=Africa/Accra

# Logging (Heroku captures stderr → visible via `heroku logs --tail`)
heroku config:set LOG_CHANNEL=stderr

# Sessions & Cache (use database to survive dyno restarts)
heroku config:set SESSION_DRIVER=database
heroku config:set CACHE_STORE=database
heroku config:set QUEUE_CONNECTION=sync

# Paystack payment keys
heroku config:set PAYSTACK_PUBLIC_KEY=pk_live_xxxx
heroku config:set PAYSTACK_SECRET_KEY=sk_live_xxxx
heroku config:set PAYSTACK_MERCHANT_EMAIL=payments@yourdomain.com

# Optional: AI features (leave blank to use mock provider)
heroku config:set AI_PROVIDER=mock
# heroku config:set OPENAI_API_KEY=sk-xxxx
# heroku config:set CLAUDE_API_KEY=sk-ant-xxxx
```

### 4. Deploy

```bash
git push heroku main
```

Heroku will:
1. Auto-detect the **PHP buildpack** (via `composer.json`)
2. Run `composer install --no-dev --prefer-dist`
3. Run the **release phase**: `php artisan migrate --force`
4. Start Apache serving `public/` via the **web dyno**

### 5. Seed demo data (optional)

```bash
heroku run php artisan db:seed --force
```

### 6. Verify the app

```bash
heroku open
heroku logs --tail
```

---

## Paystack Webhook

In your [Paystack Dashboard → Settings → Webhooks](https://dashboard.paystack.com/#/settings/developer):

- **URL:** `https://your-app-name.herokuapp.com/webhooks/paystack`
- **Events:** `charge.success`, `transfer.success`, `refund.processed`

---

## Demo Credentials

| Role       | Email                           | Password      |
|------------|---------------------------------|---------------|
| Super Admin | admin@ghanabusconnect.com      | Admin@123!    |
| Operator   | vanef@ghanabusconnect.com       | Operator@123  |
| Operator   | oatravel@ghanabusconnect.com    | Operator@123  |
| Conductor  | conductor@ghanabusconnect.com   | Conduct@123   |
| Passenger  | kofi@example.com                | Pass@1234     |
| Passenger  | ama@example.com                 | Pass@1234     |

> **Change all passwords immediately after first login.**

---

## Local Development

```bash
git clone https://github.com/Kwameboat/bus-ticket.git
cd bus-ticket
composer install
cp .env.example .env
# Edit .env with your local DB credentials
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Visit `http://localhost:8000`

---

## Traditional (cPanel) Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for cPanel/shared-hosting deployment instructions.

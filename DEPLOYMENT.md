# GhanaBus Connect — cPanel Deployment Guide
# =============================================
# Production deployment for shared cPanel hosting
# PHP 8.2+ | MySQL 8 | Apache | .htaccess

## ═══════════════════════════════════════
## STEP 1 — Upload Files
## ═══════════════════════════════════════

# Your cPanel file structure should be:
#   ~/laravel/          ← all Laravel code (app, database, routes, etc.)
#   ~/public_html/      ← only the contents of the /public folder

# 1a. Using File Manager or FTP:
#     Upload everything EXCEPT /public to: ~/laravel/
#     Upload contents of /public to:       ~/public_html/

# 1b. Edit ~/public_html/index.php — change the path on line 5 & 10:
#     FROM: require __DIR__.'/../vendor/autoload.php';
#     TO:   require __DIR__.'/../laravel/vendor/autoload.php';
#     FROM: $app = require_once __DIR__.'/../bootstrap/app.php';
#     TO:   $app = require_once __DIR__.'/../laravel/bootstrap/app.php';


## ═══════════════════════════════════════
## STEP 2 — PHP Version
## ═══════════════════════════════════════

# cPanel → MultiPHP Manager → Set domain to PHP 8.2 or 8.3
# cPanel → MultiPHP INI Editor → Set:
#   memory_limit = 256M
#   upload_max_filesize = 20M
#   post_max_size = 25M
#   max_execution_time = 120


## ═══════════════════════════════════════
## STEP 3 — MySQL Database
## ═══════════════════════════════════════

# cPanel → MySQL Databases:
#   1. Create database:  gbc_production
#   2. Create user:      gbc_user  (strong password)
#   3. Add user to DB:   ALL PRIVILEGES
#   4. Note credentials for .env


## ═══════════════════════════════════════
## STEP 4 — Environment Configuration
## ═══════════════════════════════════════

# Copy .env.example to .env in ~/laravel/
# Fill in these critical values:

APP_NAME="GhanaBus Connect"
APP_ENV=production
APP_KEY=                          # Generated in Step 6
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yourusername_gbc
DB_USERNAME=yourusername_gbc_user
DB_PASSWORD=your_secure_password

MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com

PAYSTACK_PUBLIC_KEY=pk_live_xxxxx
PAYSTACK_SECRET_KEY=sk_live_xxxxx

# For AI features (optional - system works without these):
OPENAI_API_KEY=sk-xxxxx
# OR
CLAUDE_API_KEY=sk-ant-xxxxx


## ═══════════════════════════════════════
## STEP 5 — Install Dependencies
## ═══════════════════════════════════════

# Via cPanel Terminal (or SSH):
cd ~/laravel
composer install --no-dev --optimize-autoloader

# If no composer on server, install it:
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=~/bin --filename=composer
composer install --no-dev --optimize-autoloader


## ═══════════════════════════════════════
## STEP 6 — Laravel Setup Commands
## ═══════════════════════════════════════

cd ~/laravel

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed demo data (Ghana routes, operators, sample bookings)
php artisan db:seed --force

# Create storage symlink
php artisan storage:link

# If storage:link fails on cPanel, create manually:
# ln -s ~/laravel/storage/app/public ~/public_html/storage

# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache


## ═══════════════════════════════════════
## STEP 7 — File Permissions
## ═══════════════════════════════════════

chmod -R 755 ~/laravel/storage
chmod -R 755 ~/laravel/bootstrap/cache
chmod 644 ~/laravel/.env


## ═══════════════════════════════════════
## STEP 8 — .htaccess Verification
## ═══════════════════════════════════════

# Ensure ~/public_html/.htaccess contains:
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
<IfModule mod_headers.c>
    Header set Service-Worker-Allowed "/"
</IfModule>

# If mod_rewrite not enabled, contact host or add to php.ini:
# auto_prepend_file = none


## ═══════════════════════════════════════
## STEP 9 — Cron Jobs
## ═══════════════════════════════════════

# cPanel → Cron Jobs → Add:
# Run every 5 minutes (Laravel scheduler entry point):
*/5 * * * * /usr/bin/php ~/laravel/artisan schedule:run >> /dev/null 2>&1

# This single cron handles all tasks:
# - Release expired seat locks (every 5 min)
# - Process waitlist (every 10 min)
# - Update trip statuses (every 30 min)
# - Send departure reminders (daily 6am)
# - Expire old QR tickets (hourly)


## ═══════════════════════════════════════
## STEP 10 — SSL Certificate
## ═══════════════════════════════════════

# cPanel → SSL/TLS → Let's Encrypt (AutoSSL)
# Enable "Force HTTPS Redirect" in cPanel
# Add to .env: APP_URL=https://yourdomain.com


## ═══════════════════════════════════════
## STEP 11 — Paystack Webhook
## ═══════════════════════════════════════

# In your Paystack Dashboard → Settings → Webhooks:
# Add URL: https://yourdomain.com/webhooks/paystack
# Events to enable: charge.success, transfer.success, refund.processed


## ═══════════════════════════════════════
## STEP 12 — PWA Icons
## ═══════════════════════════════════════

# Upload your bus company icons to ~/public_html/pwa-icons/
# Required sizes: 72, 96, 128, 144, 152, 192, 384, 512 (PNG)
# Also needed: maskable-512.png (icon with safe zone padding)
# Use: https://maskable.app to generate maskable icons
# Use: https://realfavicongenerator.net for all sizes


## ═══════════════════════════════════════
## STEP 13 — First Login
## ═══════════════════════════════════════

# Default admin credentials (CHANGE IMMEDIATELY):
# Email:    admin@ghanabusconnect.com
# Password: Admin@123!

# Visit: https://yourdomain.com/admin
# Go to: Settings → General → Update site name, email, etc.
# Go to: Settings → AI → Configure AI provider (or leave as "mock")
# Go to: Settings → PWA → Set your app name and colors


## ═══════════════════════════════════════
## STEP 14 — Verify PWA Installation
## ═══════════════════════════════════════

# On Android Chrome:
# 1. Visit https://yourdomain.com
# 2. Wait 3 seconds — install banner should appear
# 3. Tap "Install App" → "Install"
# 4. App appears on home screen like native app

# On iOS Safari:
# 1. Visit https://yourdomain.com
# 2. iOS banner appears with "Share → Add to Home Screen" instructions
# 3. Follow the prompt

# PWA checklist:
# ✅ HTTPS enabled
# ✅ manifest.json accessible at /manifest.json
# ✅ service-worker.js accessible at /service-worker.js
# ✅ Icons at /pwa-icons/icon-192.png and /pwa-icons/icon-512.png
# ✅ Header: Service-Worker-Allowed: /


## ═══════════════════════════════════════
## TROUBLESHOOTING
## ═══════════════════════════════════════

# 500 Error → Check ~/laravel/storage/logs/laravel.log
# 404 on routes → Check .htaccess and mod_rewrite
# DB connection → Verify DB_HOST=127.0.0.1 (not localhost)
# Storage files 404 → Re-run: php artisan storage:link
# Composer errors → Run: php artisan config:clear && php artisan cache:clear
# Session errors → Set SESSION_DRIVER=file in .env
# Cache errors → Set CACHE_STORE=file in .env
# Queue (not needed) → Set QUEUE_CONNECTION=sync in .env for cPanel


## ═══════════════════════════════════════
## DEMO CREDENTIALS
## ═══════════════════════════════════════

# Super Admin:  admin@ghanabusconnect.com    / Admin@123!
# Operator:     vanef@ghanabusconnect.com    / Operator@123
# Operator:     oatravel@ghanabusconnect.com / Operator@123
# Conductor:    conductor@ghanabusconnect.com/ Conduct@123
# Passenger:    kofi@example.com             / Pass@1234
# Passenger:    ama@example.com              / Pass@1234


## ═══════════════════════════════════════
## SEEDED DEMO DATA
## ═══════════════════════════════════════

# Routes:    Accra→Kumasi, Accra→Takoradi, Accra→Cape Coast,
#            Kumasi→Tamale, Accra→Ho, Accra→Sunyani
# Operators: VanefSTC, OA Travel & Tours, Starlite Express
# Buses:     5 buses with seat plans (30–52 seats)
# Trips:     Generated for ±14 days around today
# Bookings:  6 confirmed bookings with QR tickets
# Wallets:   All passengers have demo wallet balances
# FAQs:      13 pre-written FAQs across 4 categories
# Pages:     About, Terms, Privacy, Refund Policy, FAQ


## ═══════════════════════════════════════
## PRODUCTION CHECKLIST
## ═══════════════════════════════════════

# [ ] APP_DEBUG=false in .env
# [ ] APP_ENV=production in .env
# [ ] Admin password changed from default
# [ ] Paystack LIVE keys set (not test keys)
# [ ] Paystack webhook URL registered
# [ ] SSL certificate active
# [ ] Cron job running (test: php artisan schedule:run)
# [ ] Email sending tested (send a test booking)
# [ ] PWA install tested on mobile device
# [ ] PWA icons uploaded and correct sizes
# [ ] Storage link created (profile photos, QR codes work)
# [ ] AI provider configured or set to "mock"

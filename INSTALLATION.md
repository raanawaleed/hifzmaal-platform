# HifzMaal Installation Guide

## Prerequisites

Before installing HifzMaal, ensure your system meets these requirements:

- **PHP**: 8.1 or higher
- **Composer**: Latest version
- **Database**: MySQL 5.7+ or PostgreSQL 10+
- **Node.js**: 16.x or higher
- **NPM**: 8.x or higher
- **Web Server**: Apache or Nginx

## Docker (alternative to the manual steps below)

A `Dockerfile` + `docker-compose.yml` are provided as an alternative to the
manual VPS setup in the rest of this guide — skip straight to "Next Steps"
if you use this path.

```bash
cp .env.example .env
php artisan key:generate --show   # paste the output into .env's APP_KEY=

docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed
docker compose exec app php artisan hifzmaal:superadmin you@example.com
```

The app is then at `http://localhost:8090` (override with `APP_PORT` in
`.env` if that's taken — 8080 is a very commonly-squatted default).

What's in it:
- **`app`**: nginx + PHP-FPM in one container (via supervisord), built from
  a multi-stage `Dockerfile` (Node stage builds the Vue assets, Composer
  stage installs PHP deps, final stage is the lean runtime).
- **`queue`**: same image, running `queue:work` — required, notifications
  are queued and silently never sent without it.
- **`scheduler`**: same image, running `schedule:work` (Laravel's
  foreground scheduler loop — the containerized equivalent of the cron
  entry in Step 8 below). Drives bill/Zakat reminders, metal-rate
  refresh, and the daily backup.
- **`mysql`**: MySQL 8, with a named volume so data survives `docker
  compose down` (not `-v`, which deletes volumes too).

Two things worth knowing:
- **Migrations are deliberately not run automatically** on container
  start — that's the one-off `docker compose exec` command above, so
  scaling `app` to multiple replicas can't race on concurrent migrations.
- **Receipts (`storage/app/public`) live in a named Docker volume**, which
  only works within one Docker host. For real multi-host/multi-replica
  scaling, set `MEDIA_DISK=s3` in `.env` with real AWS credentials instead
  and drop that volume — object storage is the actual right answer there,
  a local volume is a single-host convenience.

## Step-by-Step Installation

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/hifzmaal.git
cd hifzmaal
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
# Copy environment file
Edit `.env` file with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hifzmaal
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Database Setup
```bash
# Create database (if not exists)
mysql -u root -p -e "CREATE DATABASE hifzmaal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations and seeders
php artisan migrate --seed
```

### 5. Storage Setup
```bash
# Create symbolic link
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
```

### 6. Install Frontend Dependencies
```bash
npm install
npm run build
```

### 7. Configure Queue Worker (Required in production)

Notifications (bill reminders, budget alerts, approval requests) are queued.
Without a worker they will never be delivered.

```bash
# Development
php artisan queue:work

# Production — supervise the worker so it restarts on failure.
# /etc/supervisor/conf.d/hifzmaal-worker.conf:
#
# [program:hifzmaal-worker]
# process_name=%(program_name)s_%(process_num)02d
# command=php /var/www/hifzmaal/artisan queue:work --sleep=3 --tries=3 --max-time=3600
# autostart=true
# autorestart=true
# user=www-data
# numprocs=1
# redirect_stderr=true
# stdout_logfile=/var/www/hifzmaal/storage/logs/worker.log
# stopwaitsecs=3600
```

### 8. Configure Scheduled Tasks (Required in production)

The scheduler drives bill due/overdue reminders, Zakat reminders, and
savings auto-contributions. Add to crontab:

```bash
crontab -e

# Add this line
* * * * * cd /path-to-hifzmaal && php artisan schedule:run >> /dev/null 2>&1
```

### 8a. Configure Billing (Stripe)

HifzMaal charges for the Pro plan in USD via Stripe Checkout + the Billing
Portal (Laravel Cashier). To wire it up:

1. In the [Stripe Dashboard](https://dashboard.stripe.com/products), create one
   product ("HifzMaal Pro") with a monthly and a yearly recurring USD price.
   Copy each price's ID (`price_...`).
2. In `.env`, set `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_PRICE_PRO_MONTHLY`,
   and `STRIPE_PRICE_PRO_YEARLY`.
3. Add a webhook endpoint in Stripe pointing at
   `https://your-domain.com/api/stripe/webhook`, subscribed to at least
   `customer.subscription.*` and `invoice.payment_*` events. Copy its signing
   secret into `STRIPE_WEBHOOK_SECRET`.
4. Turn on the [Customer Portal](https://dashboard.stripe.com/settings/billing/portal)
   in Stripe settings — the app links to it from `/billing` so subscribers can
   update their card or cancel.
5. `BILLING_TRIAL_DAYS`, `BILLING_FREE_MAX_FAMILIES`, and
   `BILLING_FREE_MAX_MEMBERS` (in `.env`) control the trial length and Free
   plan limits — see `config/billing.php`.

Until real keys are set, the app runs fine and Free-plan limits still apply;
only checkout/portal/webhooks will fail.

### 8a-2. Live Zakat metal prices (optional)

By default, gold/silver rates used for the Zakat nisab threshold are entered
by hand at `/admin/settings`. To keep them current automatically, sign up at
[goldapi.io](https://www.goldapi.io) and set `GOLDAPI_KEY` in `.env` — a
scheduled job refreshes rates daily at 06:00, and superadmins can also click
"Refresh now" on that settings page. Leave it blank to keep managing rates
manually; nothing else changes.

### 8a-3. Error tracking (optional)

Sign up at [sentry.io](https://sentry.io) (free tier is fine), create a Laravel
project, and set `SENTRY_LARAVEL_DSN` in `.env`. Without it, `config/sentry.php`
is a complete no-op — you'll only find out about a production crash when a
user tells you, which is exactly what this is for. `send_default_pii` is off
by default (this app handles money — don't send request bodies/IPs to a
third party without deciding to).

### 8a-4. Automated backups

`spatie/laravel-backup` is configured (`config/backup.php`) to back up the
database + `storage/app/public` (receipts) + `.env` daily at 01:30, after a
01:00 cleanup pass and a 02:00 health check — see the `Schedule::` entries
in `routes/console.php`. Requires `mysqldump` on the server's PATH (comes
with the mysql-client package). Works out of the box to the `local` disk;
set `BACKUP_DISK=s3` once you have real AWS credentials, since local-only
backups don't survive losing the whole server. Set `BACKUP_ARCHIVE_PASSWORD`
to encrypt the zip — it contains `.env`, i.e. real secrets. Failure/unhealthy
notifications email `BACKUP_NOTIFICATION_EMAIL` (falls back to
`SUPERADMIN_EMAIL`, then `MAIL_FROM_ADDRESS`) — successful backups don't
email anyone, to avoid training yourself to ignore the inbox.

To restore: `php artisan backup:list` to find one, unzip it, restore the
`db-dumps/*.sql` file with `mysql`, and copy the `storage/app/public`
contents back into place.

### 8b. Create the Superadmin

```bash
php artisan hifzmaal:superadmin you@example.com
```

(Or set `SUPERADMIN_EMAIL` in `.env` before running `php artisan db:seed`.)
The superadmin panel lives at `/admin` — manage users, families, system
categories, and Zakat metal rates there.

### 9. Start Development Server
```bash
php artisan serve
```

Visit: http://localhost:8000

## Production Deployment

### 1. Optimize Application
```bash
# Build fresh assets and make sure the Vite dev-server marker is gone.
# If public/hot exists on the server, the app serves assets from a dead
# dev-server URL and the frontend breaks entirely.
npm ci && npm run build
rm -f public/hot

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 2. Environment Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true
CACHE_STORE=database        # or redis
QUEUE_CONNECTION=database   # or redis — worker required, see step 7
MAIL_MAILER=smtp            # real provider; password reset needs it
```

### 3. Web Server Configuration

#### Apache
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path-to-hifzmaal/public

    <Directory /path-to-hifzmaal/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path-to-hifzmaal/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 4. SSL Certificate
```bash
# Using Certbot
sudo certbot --nginx -d yourdomain.com
```

### 5. Set Permissions
```bash
chown -R www-data:www-data /path-to-hifzmaal
chmod -R 755 /path-to-hifzmaal
chmod -R 775 /path-to-hifzmaal/storage
chmod -R 775 /path-to-hifzmaal/bootstrap/cache
```

## Troubleshooting

### Issue: Permission Denied
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:www-data storage bootstrap/cache
```

### Issue: Database Connection Failed

1. Check database credentials in `.env`
2. Ensure MySQL service is running
3. Test connection: `php artisan tinker` then `DB::connection()->getPdo();`

### Issue: Storage Link Not Working
```bash
# Remove existing link
rm public/storage

# Create new link
php artisan storage:link
```

## Next Steps

1. Create your first family account
2. Set up categories
3. Add family members
4. Start tracking finances

For support, visit: https://hifzmaal.com/support
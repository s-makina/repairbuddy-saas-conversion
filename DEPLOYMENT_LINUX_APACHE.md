# Production Deployment Guide — Apache on Linux

**Stack:** Laravel 11 (PHP 8.2) + Next.js 16 + MySQL + Apache 2.4 + Supervisor

This guide covers deploying both the **Laravel backend** and the **Next.js frontend** on a single Ubuntu 22.04 / Debian 12 server using Apache as the web server.

---

## Table of Contents

1. [Server Requirements](#1-server-requirements)
2. [Initial Server Setup](#2-initial-server-setup)
3. [Install PHP 8.2](#3-install-php-82)
4. [Install MySQL](#4-install-mysql)
5. [Install Node.js](#5-install-nodejs)
6. [Install Composer](#6-install-composer)
7. [Deploy the Application](#7-deploy-the-application)
8. [Configure Environment (.env)](#8-configure-environment-env)
9. [Laravel Post-Deploy Commands](#9-laravel-post-deploy-commands)
10. [File Permissions](#10-file-permissions)
11. [Apache VirtualHost — Path Mode (current slug approach)](#11-apache-virtualhost--path-mode-current-slug-approach)
12. [Apache VirtualHost — Subdomain Mode (future)](#12-apache-virtualhost--subdomain-mode-future)
13. [SSL with Let's Encrypt](#13-ssl-with-lets-encrypt)
14. [Next.js Frontend with PM2](#14-nextjs-frontend-with-pm2)
15. [Queue Worker with Supervisor](#15-queue-worker-with-supervisor)
16. [Cron Scheduler](#16-cron-scheduler)
17. [Firewall](#17-firewall)
18. [Deployment Checklist](#18-deployment-checklist)
19. [Re-deploying / Updating](#19-re-deploying--updating)

---

## 1. Server Requirements

| Component | Minimum | Recommended |
|---|---|---|
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| CPU | 2 vCPU | 4 vCPU |
| RAM | 2 GB | 4 GB |
| Disk | 20 GB SSD | 40 GB SSD |
| PHP | 8.2 | 8.3 |
| MySQL | 8.0 | 8.0 |
| Node.js | 18 LTS | 20 LTS |
| Apache | 2.4 | 2.4 |

---

## 2. Initial Server Setup

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install essential utilities
sudo apt install -y git curl unzip wget software-properties-common \
    apt-transport-https ca-certificates gnupg2

# Create a dedicated deploy user (do not run the app as root)
sudo adduser deployer
sudo usermod -aG www-data deployer
sudo usermod -aG sudo deployer

# Switch to deploy user for the rest of the setup
su - deployer
```

---

## 3. Install PHP 8.2

```bash
# Add Ondrej's PHP PPA (Ubuntu) — most up-to-date PHP packages
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.2 and all required extensions
sudo apt install -y \
    php8.2 \
    php8.2-cli \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-gd \
    php8.2-redis \
    libapache2-mod-php8.2

# Verify
php -v
```

### Tune PHP for production (`/etc/php/8.2/apache2/php.ini`)

```bash
sudo nano /etc/php/8.2/apache2/php.ini
```

Key values to set:

```ini
memory_limit = 512M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 120
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1
```

---

## 4. Install MySQL

```bash
sudo apt install -y mysql-server

# Secure the installation (set root password, remove test DB, disallow remote root)
sudo mysql_secure_installation

# Create the application database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE repairbuddy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'repairbuddy'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON repairbuddy.* TO 'repairbuddy'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 5. Install Node.js

```bash
# Install Node.js 20 LTS via NodeSource
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install PM2 globally (process manager for Next.js)
sudo npm install -g pm2

# Verify
node -v
npm -v
pm2 -v
```

---

## 6. Install Composer

```bash
cd ~
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

---

## 7. Deploy the Application

### 7a. Set up the web root

```bash
sudo mkdir -p /var/www/repairbuddy
sudo chown deployer:www-data /var/www/repairbuddy
cd /var/www/repairbuddy
```

### 7b. Clone the repository

```bash
git clone https://github.com/YOUR_ORG/YOUR_REPO.git .
# Or if you use a deploy key:
# git clone git@github.com:YOUR_ORG/YOUR_REPO.git .
```

### 7c. Install backend dependencies

```bash
cd /var/www/repairbuddy/backend

composer install --no-dev --optimize-autoloader --no-interaction
```

### 7d. Build the frontend

```bash
cd /var/www/repairbuddy/frontend

npm ci --production=false
npm run build
```

The built Next.js app lives in `frontend/.next/`.

---

## 8. Configure Environment (.env)

```bash
cd /var/www/repairbuddy/backend
cp .env.example .env
nano .env
```

Production `.env` values:

```env
APP_NAME="RepairBuddy"
APP_ENV=production
APP_KEY=                         # filled by: php artisan key:generate
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://yourdomain.com

FRONTEND_URL=https://yourdomain.com:3000
# Or if Next.js is proxied through Apache on the same domain:
# FRONTEND_URL=https://app.yourdomain.com

# ─── Tenancy ──────────────────────────────────────────────────────────────────
# Path mode (current default — no change needed)
TENANCY_RESOLUTION=path

# Subdomain mode (when ready to switch — see Section 12)
# TENANCY_RESOLUTION=subdomain
# TENANCY_BASE_DOMAIN=yourdomain.com

# ─── Database ─────────────────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=repairbuddy
DB_USERNAME=repairbuddy
DB_PASSWORD=STRONG_PASSWORD_HERE

# ─── Session ──────────────────────────────────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=.yourdomain.com   # leading dot covers all subdomains

# ─── Cache / Queue ────────────────────────────────────────────────────────────
CACHE_STORE=database
QUEUE_CONNECTION=database

# ─── Sanctum ──────────────────────────────────────────────────────────────────
SANCTUM_STATEFUL_DOMAINS="yourdomain.com,app.yourdomain.com"
# Subdomain mode:
# SANCTUM_STATEFUL_DOMAINS="yourdomain.com,*.yourdomain.com"

# ─── Mail ─────────────────────────────────────────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# ─── Branding ─────────────────────────────────────────────────────────────────
BRAND_EMAIL_LOGO_URL=https://yourdomain.com/images/logo.png
BRAND_EMAIL_LOGO_ALT="${APP_NAME}"
BRAND_PRIMARY_COLOR=#063e70
BRAND_ACCENT_COLOR=#fd6742
```

---

## 9. Laravel Post-Deploy Commands

Run these every deployment:

```bash
cd /var/www/repairbuddy/backend

# Generate app key (first deploy only)
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Seed superadmin (first deploy only)
# php artisan db:seed --class=SuperAdminSeeder --force

# Optimise for production (caches config, routes, views)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Publish Livewire assets
php artisan livewire:publish --assets

# Link storage
php artisan storage:link
```

> **Never run `route:cache` or `config:cache` while `APP_ENV=local`** — only safe in production.

---

## 10. File Permissions

```bash
cd /var/www/repairbuddy/backend

# Ownership: deployer owns files, web server (www-data) can read
sudo chown -R deployer:www-data .

# Directories: 755; files: 644
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;

# Writable directories for Laravel
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

---

## 11. Apache VirtualHost — Path Mode (current slug approach)

This is for the **current** `/t/{business}/...` URL structure.

### Enable required Apache modules

```bash
sudo a2enmod rewrite headers expires deflate
sudo systemctl restart apache2
```

### Create the VirtualHost config

```bash
sudo nano /etc/apache2/sites-available/repairbuddy.conf
```

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    DocumentRoot /var/www/repairbuddy/backend/public

    <Directory /var/www/repairbuddy/backend/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Security headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"

    # Gzip compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json
    </IfModule>

    ErrorLog ${APACHE_LOG_DIR}/repairbuddy-error.log
    CustomLog ${APACHE_LOG_DIR}/repairbuddy-access.log combined
</VirtualHost>
```

```bash
# Enable the site
sudo a2ensite repairbuddy.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

---

## 12. Apache VirtualHost — Subdomain Mode (future)

When you switch `TENANCY_RESOLUTION=subdomain`, replace the VirtualHost with this.

> **Requires a wildcard DNS A record:** `*.yourdomain.com → your_server_IP`
> Set this in your domain registrar / DNS provider before enabling.

```bash
sudo nano /etc/apache2/sites-available/repairbuddy-subdomain.conf
```

```apache
# ── Main domain (marketing/login page) ──────────────────────────────────────
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    DocumentRoot /var/www/repairbuddy/backend/public

    <Directory /var/www/repairbuddy/backend/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"

    ErrorLog ${APACHE_LOG_DIR}/repairbuddy-error.log
    CustomLog ${APACHE_LOG_DIR}/repairbuddy-access.log combined
</VirtualHost>

# ── Wildcard tenant subdomains ───────────────────────────────────────────────
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias *.yourdomain.com

    DocumentRoot /var/www/repairbuddy/backend/public

    <Directory /var/www/repairbuddy/backend/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"

    ErrorLog ${APACHE_LOG_DIR}/repairbuddy-tenant-error.log
    CustomLog ${APACHE_LOG_DIR}/repairbuddy-tenant-access.log combined
</VirtualHost>
```

```bash
sudo a2dissite repairbuddy.conf
sudo a2ensite repairbuddy-subdomain.conf
sudo systemctl reload apache2
```

After enabling subdomain mode update `.env`:
```env
TENANCY_RESOLUTION=subdomain
TENANCY_BASE_DOMAIN=yourdomain.com
SESSION_DOMAIN=.yourdomain.com
SANCTUM_STATEFUL_DOMAINS="yourdomain.com,*.yourdomain.com"
```

Then clear and re-cache:
```bash
php artisan config:clear && php artisan config:cache
```

---

## 13. SSL with Let's Encrypt

### Install Certbot

```bash
sudo apt install -y certbot python3-certbot-apache
```

### Path mode — single domain cert

```bash
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

### Subdomain mode — wildcard cert

Wildcard certs require DNS validation (cannot use HTTP challenge).

```bash
sudo certbot certonly \
    --manual \
    --preferred-challenges dns \
    -d yourdomain.com \
    -d "*.yourdomain.com"
```

Certbot will ask you to create a `_acme-challenge` TXT record in your DNS. Add it via your DNS provider, wait ~60 seconds for propagation, then press Enter.

Certs are written to `/etc/letsencrypt/live/yourdomain.com/`.

### Auto-renewal

```bash
# Test renewal
sudo certbot renew --dry-run

# Renewal is automatic via the certbot systemd timer — verify it is active:
sudo systemctl status certbot.timer
```

### Updated VirtualHost with HTTPS (path mode example)

After Certbot runs, `/etc/apache2/sites-available/repairbuddy-le-ssl.conf` is created automatically. Verify it looks like:

```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    DocumentRoot /var/www/repairbuddy/backend/public

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    <Directory /var/www/repairbuddy/backend/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"

    ErrorLog ${APACHE_LOG_DIR}/repairbuddy-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/repairbuddy-ssl-access.log combined
</VirtualHost>

# Force HTTP → HTTPS redirect
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>
```

```bash
sudo a2enmod ssl
sudo systemctl reload apache2
```

---

## 14. Next.js Frontend with PM2

The Next.js app runs as a Node.js process on port `3000` and is proxied through Apache.

### Start the Next.js app with PM2

```bash
cd /var/www/repairbuddy/frontend

# Copy and configure the frontend .env
cp .env.example .env.local
# Edit: NEXT_PUBLIC_API_BASE_URL=https://yourdomain.com
nano .env.local

# Start with PM2
pm2 start npm --name "repairbuddy-frontend" -- start

# Save PM2 process list so it survives reboots
pm2 save

# Enable PM2 startup on boot
pm2 startup
# Copy-paste and run the command it outputs
```

### Proxy Next.js through Apache

Add a new VirtualHost (or a `Location` block) for the frontend subdomain:

```bash
sudo nano /etc/apache2/sites-available/repairbuddy-app.conf
```

```apache
<VirtualHost *:443>
    ServerName app.yourdomain.com

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    # Proxy to Next.js on port 3000
    ProxyPreserveHost On
    ProxyPass        / http://127.0.0.1:3000/
    ProxyPassReverse / http://127.0.0.1:3000/

    ErrorLog  ${APACHE_LOG_DIR}/repairbuddy-app-error.log
    CustomLog ${APACHE_LOG_DIR}/repairbuddy-app-access.log combined
</VirtualHost>
```

```bash
sudo a2enmod proxy proxy_http
sudo a2ensite repairbuddy-app.conf
sudo systemctl reload apache2
```

---

## 15. Queue Worker with Supervisor

The app uses `QUEUE_CONNECTION=database`. Supervisor keeps the worker running.

```bash
sudo apt install -y supervisor
```

```bash
sudo nano /etc/supervisor/conf.d/repairbuddy-worker.conf
```

```ini
[program:repairbuddy-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/repairbuddy/backend/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/repairbuddy-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start repairbuddy-worker:*
sudo supervisorctl status
```

> `numprocs=2` starts two parallel workers. Increase based on server load.

---

## 16. Cron Scheduler

The app schedules `appointments:send-reminders` daily at 08:00.

```bash
sudo crontab -u www-data -e
```

Add:

```cron
* * * * * php /var/www/repairbuddy/backend/artisan schedule:run >> /dev/null 2>&1
```

Verify it is saved:

```bash
sudo crontab -u www-data -l
```

---

## 17. Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'    # opens ports 80 and 443
sudo ufw deny 3306              # block MySQL from outside
sudo ufw enable
sudo ufw status
```

If `www-data` needs to connect outward (for mail/APIs):

```bash
sudo ufw allow out 443
sudo ufw allow out 587   # SMTP
```

---

## 18. Deployment Checklist

Before going live, verify each item:

```
[ ] APP_DEBUG=false in .env
[ ] APP_ENV=production in .env
[ ] A strong APP_KEY is set
[ ] DB user has only GRANT on the app database (not root)
[ ] php artisan config:cache ran successfully
[ ] php artisan route:cache ran successfully
[ ] php artisan migrate --force completed with no errors
[ ] storage:link created at public/storage
[ ] storage/ and bootstrap/cache/ owned by www-data and writable
[ ] Supervisor worker is running (supervisorctl status)
[ ] Cron is set for www-data
[ ] HTTPS is working — HTTP redirects to HTTPS
[ ] HSTS header is present (curl -I https://yourdomain.com)
[ ] SESSION_DOMAIN=.yourdomain.com (leading dot)
[ ] SANCTUM_STATEFUL_DOMAINS includes the frontend domain
[ ] Firewall: port 3306 blocked externally, 80/443 open
[ ] PM2 frontend process is running (pm2 status)
[ ] PM2 startup hook is saved (pm2 save)
[ ] Let's Encrypt renewal dry-run succeeds
[ ] Queue test: php artisan queue:work --once (processes one job cleanly)
[ ] Mail test: php artisan tinker → Mail::raw('test', fn($m) => $m->to('you@test.com'))
```

---

## 19. Re-deploying / Updating

Create a deploy script at `/var/www/repairbuddy/deploy.sh`:

```bash
#!/usr/bin/env bash
set -e

APP_DIR=/var/www/repairbuddy/backend
FRONTEND_DIR=/var/www/repairbuddy/frontend

echo "==> Pulling latest code..."
git -C /var/www/repairbuddy pull origin main

echo "==> Installing backend dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --working-dir=$APP_DIR

echo "==> Running migrations..."
php $APP_DIR/artisan migrate --force

echo "==> Clearing and re-caching..."
php $APP_DIR/artisan config:cache
php $APP_DIR/artisan route:cache
php $APP_DIR/artisan view:cache
php $APP_DIR/artisan event:cache

echo "==> Restarting queue workers..."
php $APP_DIR/artisan queue:restart

echo "==> Building frontend..."
npm ci --prefix $FRONTEND_DIR
npm run build --prefix $FRONTEND_DIR

echo "==> Reloading frontend process..."
pm2 reload repairbuddy-frontend

echo "==> Done."
```

```bash
chmod +x /var/www/repairbuddy/deploy.sh

# Run a deploy
sudo -u deployer /var/www/repairbuddy/deploy.sh
```

---

## Directory Structure Reference

```
/var/www/repairbuddy/
├── backend/                  # Laravel root
│   ├── app/
│   ├── bootstrap/cache/      # must be writable by www-data
│   ├── config/
│   ├── database/
│   ├── public/               # Apache DocumentRoot
│   │   └── storage -> ../storage/app/public
│   ├── routes/
│   ├── storage/              # must be writable by www-data
│   └── .env                  # never commit this
├── frontend/                 # Next.js root
│   ├── .next/                # built output
│   ├── .env.local            # never commit this
│   └── package.json
└── deploy.sh
```

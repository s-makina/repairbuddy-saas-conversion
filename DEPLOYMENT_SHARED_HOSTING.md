# Shared Hosting Deployment Guide (Non-Docker)

This guide outlines how to deploy the **RepairBuddy SaaS** (Laravel Backend + Next.js Frontend) to a traditional shared hosting environment (CPanel, DirectAdmin, etc.).

## 1. Directory Structure

On a shared host, you typically have a `public_html` (or `www`) folder. We recommend placing the backend and frontend in subdirectories or sibling folders to keep the root clean.

### Recommended Layout:
```text
/home/user/
  ├── repairbuddy_backend/  (All Laravel files except public contents)
  ├── repairbuddy_frontend/ (Next.js build output / SSG)
  └── public_html/
      ├── api/              (Symlink to backend/public or contents of backend/public)
      └── app/              (Frontend files)
```

## 2. Backend (Laravel) Deployment

1. **Upload Files**: Upload the `backend` folder contents to `/home/user/repairbuddy_backend`.
2. **Environment**: Copy `.env.example` to `.env` and configure:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://yourdomain.com/api`
   - DB credentials provided by your host.
3. **Public Folder**: Since shared hosts usually use `public_html` as the root, you have two options:
   - **Option A (Symlink)**: Run `ln -s /home/user/repairbuddy_backend/public /home/user/public_html/api`.
   - **Option B (Move)**: Move contents of `backend/public` to `public_html/api` and update `index.php` paths.
4. **Optimization**:
   ```bash
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   ```

## 3. Frontend (Next.js) Deployment

Since Next.js requires a Node.js runtime, check if your host support **Node.js Selector** (Phusion Passenger).

### Option A: Static Export (SSG)
If the dashboard doesn't strictly require a Node server (using `output: 'export'`), you can upload the `out` folder directly to `public_html`.
> [!WARNING]
> This app uses dynamic routes (`[tenant]`). Standard SSG may require mapping.

### Option B: Node.js Runtime (Phusion Passenger)
1. **Build Locally**: Run `npm run build` locally.
2. **Upload**: Upload `.next`, `public`, `package.json`, and `node_modules` to `/home/user/repairbuddy_frontend`.
3. **Start Script**: In CPanel "Setup Node.js App":
   - **Application root**: `repairbuddy_frontend`
   - **Application URL**: `yourdomain.com`
   - **Application startup file**: `node_modules/next/dist/bin/next` (Argument: `start`)

## 4. Key Configurations

### API Base URL
Ensure the frontend `.env` points to the hosted API:
`NEXT_PUBLIC_API_BASE_URL=https://yourdomain.com/api`

### .htaccess (for Root Redirection)
If you want the main domain to point to the frontend:
```apache
RewriteEngine On
RewriteRule ^api/(.*)$ api/public/$1 [L]
RewriteRule ^$ frontend/out/index.html [L]
```

## 5. Security Checklist
- [ ] Ensure `.env` is NOT accessible via web.
- [ ] Permissions: `storage` and `bootstrap/cache` must be writable by the web server.
- [ ] Ensure `APP_DEBUG` is `false`.

## 6. Queue Worker Warning (Email Delivery)

All transactional emails — including **registration email verification** — are dispatched to the queue (`QUEUE_CONNECTION=database`). On most shared hosts you cannot run a persistent background process, so queued jobs will never be processed and users will never receive emails.

**Option A — Switch to synchronous delivery (simple, but blocks the request)**

Set in `.env`:
```env
QUEUE_CONNECTION=sync
```
Emails will be sent immediately during the HTTP request instead of being queued. This is acceptable if your SMTP provider responds quickly (e.g. Mailgun, Postmark, SendGrid). Avoid slow SMTP (Gmail, plain SMTP) as it will cause registration timeouts.

**Option B — Use a hosted queue service (recommended)**

If your host supports it, switch to Amazon SQS or a Redis-backed queue and configure a worker via a cron-driven artisan command:
```cron
* * * * * php /home/user/repairbuddy_backend/artisan queue:work --stop-when-empty >> /dev/null 2>&1
```
This runs a short-lived worker every minute via cron. Not as efficient as a persistent worker, but functional on shared hosting.

**Option C — Use a server with Supervisor (recommended for production)**

See `DEPLOYMENT_LINUX_APACHE.md` Section 15 for a full persistent queue worker setup.

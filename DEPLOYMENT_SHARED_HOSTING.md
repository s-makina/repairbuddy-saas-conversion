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

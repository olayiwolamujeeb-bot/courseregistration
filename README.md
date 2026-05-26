# Course Registration System

This project now runs through Laravel in `backend-test` and stores data in MySQL. The previous Vite dev server has been removed from the active app flow.

## What changed

- Laravel now serves the frontend at `/`
- Laravel API routes now handle `courses`, `students`, and `registrations`
- MySQL is the default database target in `.env.example`
- Session, cache, and queue defaults were changed to file/sync so XAMPP MySQL works without extra Laravel infrastructure tables

## Run with XAMPP MySQL

1. Start `Apache` and `MySQL` in XAMPP.
2. In phpMyAdmin, create a database named `course_registration`.
3. Copy `backend-test/.env.example` to `backend-test/.env`.
4. Make sure these values are set in `backend-test/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=course_registration
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

5. Install Laravel dependencies:

```bash
cd backend-test
composer install
php artisan key:generate
php artisan migrate
```

6. Start the Laravel app:

```bash
php artisan serve
```

Open `http://127.0.0.1:8000`.

## Deploy on Render

This repo is now set up for Render with Docker:

- `render.yaml` defines a Docker web service
- `backend-test/Dockerfile` builds and serves the Laravel app
- `backend-test/docker/render-start.sh` runs migrations and starts Apache on Render's assigned port

### Render environment variables

The blueprint now provisions a private MySQL service on Render and wires the Laravel app to it automatically. You only need to set the public app URL:

```env
APP_URL=https://your-service.onrender.com
```

### Exact Render dashboard setup

In Render:

1. Click `New +` -> `Blueprint`.
2. Connect this repository.
3. Render will detect `render.yaml`.
4. Create both services:
   - `course-registration`
   - `course-registration-mysql`
5. Open the web service in Render and set:

```env
APP_URL=https://course-registration.onrender.com
```

If your service name on Render is different, replace `course-registration` in `APP_URL` with your actual Render subdomain.

Recommended Render values:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_CONNECTION=mysql`
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` are filled automatically from the private MySQL service

### If you still want to use XAMPP MySQL

For local development with XAMPP, keep:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=course_registration
DB_USERNAME=root
DB_PASSWORD=
```

For Render production, do not use `127.0.0.1` unless MySQL is running inside the same Render environment, which it is not in this setup.

### Important XAMPP note

Your local XAMPP MySQL remains for local development only. Production on Render now uses the private Render MySQL service defined in `render.yaml`, which is safer than exposing your local machine's database.

## Important folders

- `backend-test/` - active Laravel application
- `backend-test/public/assets/` - compiled frontend assets now served by Laravel
- `backend-test/routes/api.php` - JSON API routes
- `backend-test/resources/views/app.blade.php` - frontend entry page
- `backend-test/Dockerfile` - Render Docker build
- `backend-test/docker/render-start.sh` - Render container startup script

## Note

The original Vue source is still in `src/` for reference, but the live app is now served from Laravel using the compiled assets copied into `backend-test/public/assets`.

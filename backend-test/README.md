# Laravel App

This folder contains the active Laravel version of the course registration system.

## Setup

1. Copy `.env.example` to `.env`.
2. Create the SQLite database file:

```bash
type nul > database\database.sqlite
```

3. Install Laravel dependencies with PHP 8.3:

```bash
php C:\Users\HP\AppData\Local\Programs\Composer\composer.phar install --no-dev
php artisan key:generate
php artisan migrate
php artisan serve
```

This local setup uses SQLite by default, so you do not need MySQL just to run the API.

## Optional MySQL setup

If you prefer XAMPP MySQL instead of SQLite:

1. Edit `.env`
2. Set `DB_CONNECTION=mysql`
3. Fill in `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`
4. Create the `course_registration` database in MySQL
5. Run `php artisan migrate`

## App behavior

- `/` serves the course registration frontend
- `/api/courses` manages courses
- `/api/students` creates and lists students
- `/api/registrations` creates and lists registrations

The frontend assets are served directly from `public/assets`, so Vite is not required for the Laravel app to serve the built frontend.

## Render

This app can deploy to Render using the Docker setup in:

- `Dockerfile`
- `docker/render-start.sh`

Use Render environment variables for `APP_URL`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

The blueprint now provisions a private MySQL service on Render and passes the database settings into the Laravel web service automatically.

Example production value you still need to set manually on the web service:

```env
APP_URL=https://course-registration.onrender.com
DB_CONNECTION=mysql
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

The `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` values come from the private service `course-registration-mysql`.

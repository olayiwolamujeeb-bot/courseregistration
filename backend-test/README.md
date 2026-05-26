# Laravel App

This folder contains the active Laravel version of the course registration system.

## Setup

1. Copy `.env.example` to `.env`.
2. Start MySQL in XAMPP and create a database named `course_registration`.
3. Run:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

## App behavior

- `/` serves the course registration frontend
- `/api/courses` manages courses
- `/api/students` creates and lists students
- `/api/registrations` creates and lists registrations

The frontend assets are served directly from `public/assets`, so Vite is no longer required for this project to run.

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

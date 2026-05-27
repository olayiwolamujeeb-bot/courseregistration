# Course Registration System

This project uses a Vue frontend with a single plain PHP API backend in `backend/`.

## Local run

1. Start the backend:

```bash
npm run backend:serve
```

2. In another terminal, start the frontend:

```bash
npm run dev
```

3. Open the Vite URL shown in the terminal.

The frontend is configured to call:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Admin login is validated by the backend API. Configure the server with:

```env
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin
```

## Backend details

- `backend/router.php` routes `/api/*` requests to the PHP API
- `backend/api/index.php` exposes `courses`, `students`, `registrations`, and `health`
- `backend/api/db.php` uses SQLite by default and auto-creates the database and tables
- the SQLite database file is `backend/database.sqlite`

## Optional MySQL

The plain PHP backend can still use MySQL if you set environment variables before starting it:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=course_registration
DB_USERNAME=root
DB_PASSWORD=
```

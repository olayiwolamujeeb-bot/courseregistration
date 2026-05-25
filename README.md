# Course Registration System

A Vue 3 + Vite course registration dashboard for managing a course catalog, student registrations, and admin workflows.

## Features

- Admin login and student login flows
- Admin course management (add, edit, delete, and filter courses)
- Student course registration view
- Local dev API middleware for `/api` routes during development
- Tailwind-based UI

## Tech Stack

- Vue 3
- Vite
- Tailwind CSS v4
- jsPDF (used by the app dependencies)

## Project Structure

- `src/` — Vue app source
- `src/components/` — reusable UI components
- `src/services/api.js` — API client helpers
- `vite.config.js` — Vite config and local `/api` middleware
- `render.yaml` — Render deployment config

## Getting Started

### 1. Install dependencies

```bash
npm install
```

### 2. Run the development server

```bash
npm run dev
```

The app will be available at `http://localhost:5174/` by default.

### 3. Build for production

```bash
npm run build
```

### 4. Preview the production build

```bash
npm run preview
```

## Authentication

### Admin login

The default admin credentials are:

- **Username:** `admin`
- **Password:** `admin`

You can override these with environment variables:

- `VITE_ADMIN_USERNAME`
- `VITE_ADMIN_PASSWORD`

### Student login

Students enter their name and select a level to access the student registration flow.

## API behavior

During development, Vite serves a local middleware at `/api` so the app can create, read, update, and delete courses, registrations, and students without requiring a separate backend.

## Deployment

1. Push this repository to GitHub.
2. Create a new **Web Service** on Render.
3. Connect the GitHub repository.
4. Render will use the `render.yaml` config to build and serve the app.

## Notes

- Local development data is kept in memory by the Vite API middleware.
- The app is a frontend-focused demo and is not a full production authentication system.

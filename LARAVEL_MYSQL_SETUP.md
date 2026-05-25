# Laravel + MySQL Setup


`http://127.0.0.1:8000/api`

You can change that by creating a `.env` file in the Vue project root with:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

## 1. Fix Composer SSL on this machine

Laravel scaffolding failed because Composer could not verify the SSL certificate while downloading packages.

Check your Composer CA file:

```powershell
composer config --global cafile
```

If needed, point Composer to a valid `cacert.pem` file or re-enable the correct OpenSSL/certificate bundle in your PHP and Composer setup.

## 2. Create the Laravel project

Run this in the project root after the SSL issue is fixed:

```powershell
composer create-project laravel/laravel backend
```

## 3. Configure MySQL in `backend/.env`

```env
APP_NAME="Course Registration"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=course_registration
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in MySQL:

```sql
CREATE DATABASE course_registration;
```

## 4. Create models and migrations

```powershell
php artisan make:model Course -mcr
php artisan make:model Student -mcr
php artisan make:model Registration -mcr
```

Use these columns:

### `courses`

- `id`
- `title`
- `code` unique
- `level`
- `semester`
- `unit`
- `type`
- timestamps

### `students`

- `id`
- `name`
- `level`
- `email` nullable
- `matric` nullable
- timestamps

### `registrations`

- `id`
- `student_id` foreign key
- `course_ids` json
- timestamps

## 5. API routes

Add this to `backend/routes/api.php`:

```php
use App\Http\Controllers\CourseController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::apiResource('courses', CourseController::class);
Route::apiResource('students', StudentController::class)->only(['index', 'store']);
Route::apiResource('registrations', RegistrationController::class)->only(['index', 'store']);
```

## 6. Controller behavior expected by the Vue app

### `GET /api/courses`

Return:

```json
[
  {
    "id": 1,
    "title": "Physics 101",
    "code": "PHY101",
    "level": "100",
    "semester": "First Semester",
    "unit": 3,
    "type": "Core"
  }
]
```

### `POST /api/courses`

Accept:

```json
{
  "title": "Physics 101",
  "code": "PHY101",
  "level": "100",
  "semester": "First Semester",
  "unit": 3,
  "type": "Core"
}
```

### `PUT /api/courses/{id}`

Accept the same payload as create and return the updated course.

### `DELETE /api/courses/{id}`

Return `204 No Content`.

### `GET /api/students`

Return all students.

### `POST /api/students`

Accept:

```json
{
  "name": "John Doe",
  "level": "100"
}
```

Return the saved student.

### `GET /api/registrations`

Return:

```json
[
  {
    "id": 1,
    "student_id": 1,
    "course_ids": [1, 2],
    "created_at": "2026-05-24T10:00:00.000000Z"
  }
]
```

### `POST /api/registrations`

Accept:

```json
{
  "student_id": 1,
  "course_ids": [1, 2]
}
```

Return the saved registration.

## 7. Enable CORS if frontend runs on Vite

Allow `http://localhost:5173` in Laravel CORS config if needed.

## 8. Run Laravel

```powershell
cd backend
php artisan migrate
php artisan serve
```

## 9. Run the Vue frontend

From the project root:

```powershell
npm run dev
```

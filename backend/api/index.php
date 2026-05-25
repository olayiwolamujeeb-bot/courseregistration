<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = require __DIR__ . '/db.php';

function json_response($statusCode, $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function read_json_body()
{
    $body = file_get_contents('php://input');
    if ($body === false || trim($body) === '') {
        return [];
    }

    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : [];
}

function normalize_course($row)
{
    return [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'code' => $row['code'],
        'level' => (string) $row['level'],
        'semester' => $row['semester'],
        'unit' => (int) $row['unit'],
        'type' => $row['type'],
    ];
}

function normalize_student($row)
{
    return [
        'id' => (int) $row['id'],
        'name' => $row['name'],
        'level' => (string) $row['level'],
        'email' => $row['email'] ?? '',
        'matric' => $row['matric'] ?? '',
    ];
}

function normalize_registration($row)
{
    $courseIds = json_decode($row['course_ids'] ?? '[]', true);
    if (!is_array($courseIds)) {
        $courseIds = [];
    }

    return [
        'id' => (int) $row['id'],
        'studentId' => (int) $row['student_id'],
        'courseIds' => array_map('intval', $courseIds),
        'createdAt' => $row['created_at'] ?? null,
    ];
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$uri = str_replace('/course-registration-api/api', '', $uri);
$uri = str_replace('/api', '', $uri);
$uri = trim($uri, '/');
$segments = $uri === '' ? [] : explode('/', $uri);
$resource = $segments[0] ?? '';
$resourceId = $segments[1] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($resource === 'courses') {
        if ($method === 'GET') {
            $statement = $pdo->query('SELECT * FROM courses ORDER BY id DESC');
            $courses = array_map('normalize_course', $statement->fetchAll());
            json_response(200, $courses);
        }

        if ($method === 'POST') {
            $payload = read_json_body();
            $title = trim((string) ($payload['title'] ?? ''));
            $code = strtoupper(trim((string) ($payload['code'] ?? '')));
            $level = trim((string) ($payload['level'] ?? ''));
            $semester = trim((string) ($payload['semester'] ?? ''));
            $unit = (int) ($payload['unit'] ?? 0);
            $type = trim((string) ($payload['type'] ?? ''));

            if ($title === '' || $code === '' || $level === '' || $semester === '' || $type === '' || $unit <= 0) {
                json_response(422, ['message' => 'Course payload is incomplete.']);
            }

            $statement = $pdo->prepare('INSERT INTO courses (title, code, level, semester, unit, type) VALUES (:title, :code, :level, :semester, :unit, :type)');
            $statement->execute([
                ':title' => $title,
                ':code' => $code,
                ':level' => $level,
                ':semester' => $semester,
                ':unit' => $unit,
                ':type' => $type,
            ]);

            json_response(201, normalize_course([
                'id' => (int) $pdo->lastInsertId(),
                'title' => $title,
                'code' => $code,
                'level' => $level,
                'semester' => $semester,
                'unit' => $unit,
                'type' => $type,
            ]));
        }

        if ($method === 'PUT' && $resourceId !== null) {
            $payload = read_json_body();
            $title = trim((string) ($payload['title'] ?? ''));
            $code = strtoupper(trim((string) ($payload['code'] ?? '')));
            $level = trim((string) ($payload['level'] ?? ''));
            $semester = trim((string) ($payload['semester'] ?? ''));
            $unit = (int) ($payload['unit'] ?? 0);
            $type = trim((string) ($payload['type'] ?? ''));

            if ($title === '' || $code === '' || $level === '' || $semester === '' || $type === '' || $unit <= 0) {
                json_response(422, ['message' => 'Course payload is incomplete.']);
            }

            $statement = $pdo->prepare('UPDATE courses SET title = :title, code = :code, level = :level, semester = :semester, unit = :unit, type = :type WHERE id = :id');
            $statement->execute([
                ':title' => $title,
                ':code' => $code,
                ':level' => $level,
                ':semester' => $semester,
                ':unit' => $unit,
                ':type' => $type,
                ':id' => (int) $resourceId,
            ]);

            $updated = $pdo->prepare('SELECT * FROM courses WHERE id = :id');
            $updated->execute([':id' => (int) $resourceId]);
            $row = $updated->fetch();

            if (!$row) {
                json_response(404, ['message' => 'Course not found.']);
            }

            json_response(200, normalize_course($row));
        }

        if ($method === 'DELETE' && $resourceId !== null) {
            $statement = $pdo->prepare('DELETE FROM courses WHERE id = :id');
            $statement->execute([':id' => (int) $resourceId]);

            if ($statement->rowCount() === 0) {
                json_response(404, ['message' => 'Course not found.']);
            }

            $registrationStatement = $pdo->prepare('SELECT * FROM registrations');
            $registrationStatement->execute();
            $registrations = $registrationStatement->fetchAll();

            foreach ($registrations as $registration) {
                $courseIds = json_decode($registration['course_ids'] ?? '[]', true);
                if (!is_array($courseIds)) {
                    $courseIds = [];
                }

                $filtered = array_values(array_filter($courseIds, fn($courseId) => (int) $courseId !== (int) $resourceId));
                if ($filtered !== $courseIds) {
                    $update = $pdo->prepare('UPDATE registrations SET course_ids = :course_ids WHERE id = :id');
                    $update->execute([
                        ':course_ids' => json_encode($filtered),
                        ':id' => (int) $registration['id'],
                    ]);
                }
            }

            http_response_code(204);
            exit;
        }
    }

    if ($resource === 'students') {
        if ($method === 'GET') {
            $statement = $pdo->query('SELECT * FROM students ORDER BY id DESC');
            $students = array_map('normalize_student', $statement->fetchAll());
            json_response(200, $students);
        }

        if ($method === 'POST') {
            $payload = read_json_body();
            $name = trim((string) ($payload['name'] ?? ''));
            $level = trim((string) ($payload['level'] ?? ''));
            $email = trim((string) ($payload['email'] ?? ''));
            $matric = trim((string) ($payload['matric'] ?? ''));

            if ($name === '' || $level === '') {
                json_response(422, ['message' => 'Student payload is incomplete.']);
            }

            $statement = $pdo->prepare('INSERT INTO students (name, level, email, matric) VALUES (:name, :level, :email, :matric)');
            $statement->execute([
                ':name' => $name,
                ':level' => $level,
                ':email' => $email,
                ':matric' => $matric,
            ]);

            json_response(201, normalize_student([
                'id' => (int) $pdo->lastInsertId(),
                'name' => $name,
                'level' => $level,
                'email' => $email,
                'matric' => $matric,
            ]));
        }
    }

    if ($resource === 'registrations') {
        if ($method === 'GET') {
            $statement = $pdo->query('SELECT * FROM registrations ORDER BY id DESC');
            $registrations = array_map('normalize_registration', $statement->fetchAll());
            json_response(200, $registrations);
        }

        if ($method === 'POST') {
            $payload = read_json_body();
            $studentId = (int) ($payload['student_id'] ?? 0);
            $courseIds = $payload['course_ids'] ?? [];

            if ($studentId <= 0 || !is_array($courseIds)) {
                json_response(422, ['message' => 'Registration payload is incomplete.']);
            }

            $courseIds = array_values(array_map('intval', $courseIds));
            $statement = $pdo->prepare('INSERT INTO registrations (student_id, course_ids) VALUES (:student_id, :course_ids)');
            $statement->execute([
                ':student_id' => $studentId,
                ':course_ids' => json_encode($courseIds),
            ]);

            json_response(201, normalize_registration([
                'id' => (int) $pdo->lastInsertId(),
                'student_id' => $studentId,
                'course_ids' => json_encode($courseIds),
                'created_at' => date('c'),
            ]));
        }
    }

    json_response(404, ['message' => 'API route not found.']);
} catch (Throwable $error) {
    json_response(500, ['message' => $error->getMessage()]);
}

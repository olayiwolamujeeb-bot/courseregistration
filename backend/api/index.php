<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/env.php';
load_env_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
$storage = require __DIR__ . '/db.php';

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
    $courseIds = $row['course_ids'] ?? [];
    if (!is_array($courseIds)) {
        $courseIds = json_decode((string) $courseIds, true);
    }
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

function read_env_value($key, $default = '')
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    return (string) $value;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$uri = preg_replace('#^/course-registration-api/api#', '', $uri) ?? $uri;
$uri = preg_replace('#^/api#', '', $uri) ?? $uri;
$uri = trim($uri, '/');
$segments = $uri === '' ? [] : explode('/', $uri);
$resource = $segments[0] ?? '';
$resourceId = $segments[1] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($resource === 'health' && $method === 'GET') {
        json_response(200, ['ok' => true]);
    }

    if ($resource === 'auth' && $resourceId === 'admin-login' && $method === 'POST') {
        $payload = read_json_body();
        $name = trim((string) ($payload['name'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($name === '' || $password === '') {
            json_response(422, ['message' => 'Admin name and password are required.']);
        }

        $configuredAdminUsername = trim(read_env_value('ADMIN_USERNAME', read_env_value('VITE_ADMIN_USERNAME', 'admin')));
        $configuredAdminPassword = read_env_value('ADMIN_PASSWORD', read_env_value('VITE_ADMIN_PASSWORD', 'admin'));

        if ($configuredAdminUsername === '' || $configuredAdminPassword === '') {
            json_response(500, ['message' => 'Admin login is not configured on the server.']);
        }

        if (strcasecmp($name, $configuredAdminUsername) !== 0 || !hash_equals($configuredAdminPassword, $password)) {
            json_response(401, ['message' => 'Invalid admin login.']);
        }

        json_response(200, [
            'role' => 'admin',
            'name' => $configuredAdminUsername,
        ]);
    }

    if ($resource === 'auth' && $resourceId === 'student-login' && $method === 'POST') {
        $payload = read_json_body();
        $name = trim((string) ($payload['name'] ?? ''));
        $level = trim((string) ($payload['level'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $matric = trim((string) ($payload['matric'] ?? ''));

        if ($name === '' || $level === '') {
            json_response(422, ['message' => 'Student name and level are required.']);
        }

        if (is_json_store($storage)) {
            $data = json_store_read($storage);
            $matchedStudent = null;

            foreach ($data['students'] as $student) {
                $sameMatric = $matric !== '' && strcasecmp((string) ($student['matric'] ?? ''), $matric) === 0;
                $sameEmail = $email !== '' && strcasecmp((string) ($student['email'] ?? ''), $email) === 0;
                $sameNameAndLevel = strcasecmp((string) ($student['name'] ?? ''), $name) === 0
                    && (string) ($student['level'] ?? '') === $level;

                if ($sameMatric || $sameEmail || $sameNameAndLevel) {
                    $matchedStudent = $student;
                    break;
                }
            }

            if ($matchedStudent !== null) {
                if ($email !== '') {
                    $matchedStudent['email'] = $email;
                }
                if ($matric !== '') {
                    $matchedStudent['matric'] = $matric;
                }
                $matchedStudent['name'] = $name;
                $matchedStudent['level'] = $level;
                $matchedStudent['updated_at'] = date('c');

                $studentIndex = json_store_find_index_by_id($data['students'], $matchedStudent['id']);
                if ($studentIndex >= 0) {
                    $data['students'][$studentIndex] = $matchedStudent;
                    json_store_write($storage, $data);
                }

                json_response(200, normalize_student($matchedStudent));
            }

            $record = [
                'id' => json_store_next_id($data, 'student_id'),
                'name' => $name,
                'level' => $level,
                'email' => $email,
                'matric' => $matric,
                'created_at' => date('c'),
                'updated_at' => date('c'),
            ];

            $data['students'][] = $record;
            json_store_write($storage, $data);
            json_response(201, normalize_student($record));
        }

        if ($matric !== '') {
            $studentStatement = $storage->prepare('SELECT * FROM students WHERE matric = :matric LIMIT 1');
            $studentStatement->execute([':matric' => $matric]);
            $existingStudent = $studentStatement->fetch();
        } elseif ($email !== '') {
            $studentStatement = $storage->prepare('SELECT * FROM students WHERE email = :email LIMIT 1');
            $studentStatement->execute([':email' => $email]);
            $existingStudent = $studentStatement->fetch();
        } else {
            $studentStatement = $storage->prepare('SELECT * FROM students WHERE LOWER(name) = LOWER(:name) AND level = :level LIMIT 1');
            $studentStatement->execute([
                ':name' => $name,
                ':level' => $level,
            ]);
            $existingStudent = $studentStatement->fetch();
        }

        if ($existingStudent) {
            $updateStatement = $storage->prepare('UPDATE students SET name = :name, level = :level, email = :email, matric = :matric WHERE id = :id');
            $updateStatement->execute([
                ':name' => $name,
                ':level' => $level,
                ':email' => $email !== '' ? $email : ($existingStudent['email'] ?? ''),
                ':matric' => $matric !== '' ? $matric : ($existingStudent['matric'] ?? ''),
                ':id' => (int) $existingStudent['id'],
            ]);

            $freshStatement = $storage->prepare('SELECT * FROM students WHERE id = :id');
            $freshStatement->execute([':id' => (int) $existingStudent['id']]);
            json_response(200, normalize_student($freshStatement->fetch()));
        }

        $statement = $storage->prepare('INSERT INTO students (name, level, email, matric) VALUES (:name, :level, :email, :matric)');
        $statement->execute([
            ':name' => $name,
            ':level' => $level,
            ':email' => $email,
            ':matric' => $matric,
        ]);

        json_response(201, normalize_student([
            'id' => (int) $storage->lastInsertId(),
            'name' => $name,
            'level' => $level,
            'email' => $email,
            'matric' => $matric,
        ]));
    }

    if ($resource === 'courses') {
        if ($method === 'GET') {
            if (is_json_store($storage)) {
                $data = json_store_read($storage);
                $courses = $data['courses'];
                json_store_sort_desc_by_id($courses);
            } else {
                $statement = $storage->query('SELECT * FROM courses ORDER BY id DESC');
                $courses = $statement->fetchAll();
            }

            $courses = array_map('normalize_course', $courses);
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

            if (is_json_store($storage)) {
                $data = json_store_read($storage);
                foreach ($data['courses'] as $course) {
                    if (strcasecmp((string) ($course['code'] ?? ''), $code) === 0) {
                        json_response(422, ['message' => 'A course with this code already exists.']);
                    }
                }

                $record = [
                    'id' => json_store_next_id($data, 'course_id'),
                    'title' => $title,
                    'code' => $code,
                    'level' => $level,
                    'semester' => $semester,
                    'unit' => $unit,
                    'type' => $type,
                    'created_at' => date('c'),
                    'updated_at' => date('c'),
                ];

                $data['courses'][] = $record;
                json_store_write($storage, $data);
                json_response(201, normalize_course($record));
            }

            $duplicate = $storage->prepare('SELECT id FROM courses WHERE code = :code');
            $duplicate->execute([':code' => $code]);
            if ($duplicate->fetch()) {
                json_response(422, ['message' => 'A course with this code already exists.']);
            }

            $statement = $storage->prepare('INSERT INTO courses (title, code, level, semester, unit, type) VALUES (:title, :code, :level, :semester, :unit, :type)');
            $statement->execute([
                ':title' => $title,
                ':code' => $code,
                ':level' => $level,
                ':semester' => $semester,
                ':unit' => $unit,
                ':type' => $type,
            ]);

            json_response(201, normalize_course([
                'id' => (int) $storage->lastInsertId(),
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

            if (is_json_store($storage)) {
                $data = json_store_read($storage);
                $courseIndex = json_store_find_index_by_id($data['courses'], $resourceId);

                if ($courseIndex < 0) {
                    json_response(404, ['message' => 'Course not found.']);
                }

                foreach ($data['courses'] as $existingCourse) {
                    if ((int) $existingCourse['id'] !== (int) $resourceId && strcasecmp((string) ($existingCourse['code'] ?? ''), $code) === 0) {
                        json_response(422, ['message' => 'A course with this code already exists.']);
                    }
                }

                $data['courses'][$courseIndex] = array_merge($data['courses'][$courseIndex], [
                    'title' => $title,
                    'code' => $code,
                    'level' => $level,
                    'semester' => $semester,
                    'unit' => $unit,
                    'type' => $type,
                    'updated_at' => date('c'),
                ]);

                json_store_write($storage, $data);
                json_response(200, normalize_course($data['courses'][$courseIndex]));
            }

            $duplicate = $storage->prepare('SELECT id FROM courses WHERE code = :code AND id != :id');
            $duplicate->execute([
                ':code' => $code,
                ':id' => (int) $resourceId,
            ]);
            if ($duplicate->fetch()) {
                json_response(422, ['message' => 'A course with this code already exists.']);
            }

            $statement = $storage->prepare('UPDATE courses SET title = :title, code = :code, level = :level, semester = :semester, unit = :unit, type = :type WHERE id = :id');
            $statement->execute([
                ':title' => $title,
                ':code' => $code,
                ':level' => $level,
                ':semester' => $semester,
                ':unit' => $unit,
                ':type' => $type,
                ':id' => (int) $resourceId,
            ]);

            $updated = $storage->prepare('SELECT * FROM courses WHERE id = :id');
            $updated->execute([':id' => (int) $resourceId]);
            $row = $updated->fetch();

            if (!$row) {
                json_response(404, ['message' => 'Course not found.']);
            }

            json_response(200, normalize_course($row));
        }

        if ($method === 'DELETE' && $resourceId !== null) {
            if (is_json_store($storage)) {
                $data = json_store_read($storage);
                $courseIndex = json_store_find_index_by_id($data['courses'], $resourceId);

                if ($courseIndex < 0) {
                    json_response(404, ['message' => 'Course not found.']);
                }

                array_splice($data['courses'], $courseIndex, 1);

                foreach ($data['registrations'] as &$registration) {
                    $courseIds = is_array($registration['course_ids'] ?? null)
                        ? $registration['course_ids']
                        : json_decode((string) ($registration['course_ids'] ?? '[]'), true);

                    if (!is_array($courseIds)) {
                        $courseIds = [];
                    }

                    $registration['course_ids'] = array_values(array_filter($courseIds, fn($courseId) => (int) $courseId !== (int) $resourceId));
                }
                unset($registration);

                json_store_write($storage, $data);
                http_response_code(204);
                exit;
            }

            $statement = $storage->prepare('DELETE FROM courses WHERE id = :id');
            $statement->execute([':id' => (int) $resourceId]);

            if ($statement->rowCount() === 0) {
                json_response(404, ['message' => 'Course not found.']);
            }

            $registrationStatement = $storage->prepare('SELECT * FROM registrations');
            $registrationStatement->execute();
            $registrations = $registrationStatement->fetchAll();

            foreach ($registrations as $registration) {
                $courseIds = json_decode($registration['course_ids'] ?? '[]', true);
                if (!is_array($courseIds)) {
                    $courseIds = [];
                }

                $filtered = array_values(array_filter($courseIds, fn($courseId) => (int) $courseId !== (int) $resourceId));
                if ($filtered !== $courseIds) {
                    $update = $storage->prepare('UPDATE registrations SET course_ids = :course_ids WHERE id = :id');
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
            if (is_json_store($storage)) {
                $data = json_store_read($storage);
                $students = $data['students'];
                json_store_sort_desc_by_id($students);
            } else {
                $statement = $storage->query('SELECT * FROM students ORDER BY id DESC');
                $students = $statement->fetchAll();
            }

            $students = array_map('normalize_student', $students);
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

            if (is_json_store($storage)) {
                $data = json_store_read($storage);
                $record = [
                    'id' => json_store_next_id($data, 'student_id'),
                    'name' => $name,
                    'level' => $level,
                    'email' => $email,
                    'matric' => $matric,
                    'created_at' => date('c'),
                    'updated_at' => date('c'),
                ];

                $data['students'][] = $record;
                json_store_write($storage, $data);
                json_response(201, normalize_student($record));
            }

            $statement = $storage->prepare('INSERT INTO students (name, level, email, matric) VALUES (:name, :level, :email, :matric)');
            $statement->execute([
                ':name' => $name,
                ':level' => $level,
                ':email' => $email,
                ':matric' => $matric,
            ]);

            json_response(201, normalize_student([
                'id' => (int) $storage->lastInsertId(),
                'name' => $name,
                'level' => $level,
                'email' => $email,
                'matric' => $matric,
            ]));
        }
    }

    if ($resource === 'registrations') {
        if ($method === 'GET') {
            if (is_json_store($storage)) {
                $data = json_store_read($storage);
                $registrations = $data['registrations'];
                json_store_sort_desc_by_id($registrations);
            } else {
                $statement = $storage->query('SELECT * FROM registrations ORDER BY id DESC');
                $registrations = $statement->fetchAll();
            }

            $registrations = array_map('normalize_registration', $registrations);
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
            if ($courseIds === []) {
                json_response(422, ['message' => 'Select at least one course.']);
            }

            if (is_json_store($storage)) {
                $data = json_store_read($storage);
                $studentExists = json_store_find_index_by_id($data['students'], $studentId) >= 0;

                if (!$studentExists) {
                    json_response(422, ['message' => 'Student does not exist.']);
                }

                $existingCourseIds = array_map(fn($course) => (int) $course['id'], $data['courses']);
                $matchedCourseIds = array_values(array_intersect($courseIds, $existingCourseIds));
                sort($matchedCourseIds);
                $sortedCourseIds = $courseIds;
                sort($sortedCourseIds);

                if ($matchedCourseIds !== $sortedCourseIds) {
                    json_response(422, ['message' => 'One or more selected courses do not exist.']);
                }

                $record = [
                    'id' => json_store_next_id($data, 'registration_id'),
                    'student_id' => $studentId,
                    'course_ids' => $courseIds,
                    'created_at' => date('c'),
                    'updated_at' => date('c'),
                ];

                $data['registrations'][] = $record;
                json_store_write($storage, $data);
                json_response(201, normalize_registration($record));
            }

            $studentStatement = $storage->prepare('SELECT id FROM students WHERE id = :id');
            $studentStatement->execute([':id' => $studentId]);
            if (!$studentStatement->fetch()) {
                json_response(422, ['message' => 'Student does not exist.']);
            }

            $coursePlaceholders = implode(', ', array_fill(0, count($courseIds), '?'));
            $courseStatement = $storage->prepare("SELECT id FROM courses WHERE id IN ({$coursePlaceholders})");
            $courseStatement->execute($courseIds);
            $matchedCourseIds = array_map('intval', array_column($courseStatement->fetchAll(), 'id'));

            sort($matchedCourseIds);
            $sortedCourseIds = $courseIds;
            sort($sortedCourseIds);

            if ($matchedCourseIds !== $sortedCourseIds) {
                json_response(422, ['message' => 'One or more selected courses do not exist.']);
            }

            $statement = $storage->prepare('INSERT INTO registrations (student_id, course_ids) VALUES (:student_id, :course_ids)');
            $statement->execute([
                ':student_id' => $studentId,
                ':course_ids' => json_encode($courseIds),
            ]);

            json_response(201, normalize_registration([
                'id' => (int) $storage->lastInsertId(),
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

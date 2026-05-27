<?php

function is_json_store($storage)
{
    return is_array($storage) && ($storage['driver'] ?? null) === 'json';
}

function ensure_json_store_file($path)
{
    $directory = dirname($path);
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    if (!file_exists($path)) {
        file_put_contents($path, json_encode([
            'courses' => [],
            'students' => [],
            'registrations' => [],
            'meta' => [
                'course_id' => 0,
                'student_id' => 0,
                'registration_id' => 0,
            ],
        ], JSON_PRETTY_PRINT));
    }
}

function json_store_read($storage)
{
    ensure_json_store_file($storage['path']);
    $raw = file_get_contents($storage['path']);
    $data = json_decode($raw ?: '', true);

    if (!is_array($data)) {
        $data = [];
    }

    $data['courses'] = is_array($data['courses'] ?? null) ? $data['courses'] : [];
    $data['students'] = is_array($data['students'] ?? null) ? $data['students'] : [];
    $data['registrations'] = is_array($data['registrations'] ?? null) ? $data['registrations'] : [];
    $data['meta'] = is_array($data['meta'] ?? null) ? $data['meta'] : [];
    $data['meta']['course_id'] = (int) ($data['meta']['course_id'] ?? 0);
    $data['meta']['student_id'] = (int) ($data['meta']['student_id'] ?? 0);
    $data['meta']['registration_id'] = (int) ($data['meta']['registration_id'] ?? 0);

    return $data;
}

function json_store_write($storage, $data)
{
    ensure_json_store_file($storage['path']);
    file_put_contents($storage['path'], json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function json_store_next_id(&$data, $metaKey)
{
    $data['meta'][$metaKey] = (int) ($data['meta'][$metaKey] ?? 0) + 1;
    return $data['meta'][$metaKey];
}

function json_store_find_index_by_id($items, $id)
{
    foreach ($items as $index => $item) {
        if ((int) ($item['id'] ?? 0) === (int) $id) {
            return $index;
        }
    }

    return -1;
}

function json_store_sort_desc_by_id(&$items)
{
    usort($items, fn($left, $right) => (int) $right['id'] <=> (int) $left['id']);
}

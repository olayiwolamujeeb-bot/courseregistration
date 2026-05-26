<?php

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicFile = __DIR__ . $requestUri;

if ($requestUri !== '/' && is_file($publicFile)) {
    return false;
}

if ($requestUri === '/' || str_starts_with($requestUri, '/api')) {
    require __DIR__ . '/api/index.php';
    return true;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['message' => 'Route not found.']);

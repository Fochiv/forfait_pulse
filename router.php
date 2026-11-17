<?php
declare(strict_types=1);

$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
if (preg_match('~^/(?:\.(?!well-known(?:/|$))|attached_assets|config|includes|storage)(?:/|$)~i', $path)
    || preg_match('~^/replit\.md$~i', $path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Not found');
}

$target = __DIR__ . $path;
if (is_file($target) || (str_ends_with($path, '/') && is_file($target . 'index.php'))) {
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Page introuvable.';

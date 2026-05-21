<?php
header('Content-Type: application/json');

$url = getenv('DATABASE_URL');
if (!$url) {
    echo json_encode(['error' => 'DATABASE_URL not set']);
    exit;
}

try {
    $parts = parse_url($url);
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $parts['host'],
        $parts['port'] ?? 5432,
        ltrim($parts['path'], '/')
    );
    $pdo = new PDO($dsn, $parts['user'], $parts['pass'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $stmt = $pdo->prepare('INSERT INTO visits (path) VALUES (?)');
    $stmt->execute([$path]);

    $row = $pdo->query('SELECT COUNT(*)::int AS c FROM visits')->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'message' => 'Hello from Embr PHP + managed Postgres',
        'phpVersion' => PHP_VERSION,
        'visitsRecorded' => $row['c'],
        'lastPath' => $path,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

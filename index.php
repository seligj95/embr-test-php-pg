<?php
header('Content-Type: application/json');

$url = getenv('DATABASE_URL');
if (!$url) {
    // Dump all env vars whose name hints at DB config so we can see what the runtime did inject.
    $all = [];
    foreach ($_ENV as $k => $v) {
        if (preg_match('/(?:DB|DATABASE|PG|POSTGRES|EMBR)/i', $k)) {
            $all[$k] = strlen((string)$v) > 80 ? substr((string)$v, 0, 80) . '…' : $v;
        }
    }
    // Also try $_SERVER and getenv() for the well-known names directly
    $directProbe = [];
    foreach (['DATABASE_URL','POSTGRES_URL','PG_URL','EMBR_DATABASE_URL'] as $name) {
        $directProbe[$name] = ['_ENV' => $_ENV[$name] ?? null, '_SERVER' => $_SERVER[$name] ?? null, 'getenv' => getenv($name) ?: null];
    }
    echo json_encode([
        'error' => 'DATABASE_URL not set',
        'dbRelatedEnv' => $all,
        'directProbe' => $directProbe,
        'envKeysCount' => count($_ENV),
        'envKeysSample' => array_slice(array_keys($_ENV), 0, 20),
    ]);
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

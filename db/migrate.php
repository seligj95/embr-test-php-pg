<?php
$url = getenv('DATABASE_URL');
if (!$url) { fwrite(STDERR, "DATABASE_URL not set\n"); exit(1); }
$parts = parse_url($url);
$dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
    $parts['host'], $parts['port'] ?? 5432, ltrim($parts['path'], '/'));
$pdo = new PDO($dsn, $parts['user'], $parts['pass'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE TABLE IF NOT EXISTS visits (id SERIAL PRIMARY KEY, path TEXT NOT NULL, visited_at TIMESTAMPTZ NOT NULL DEFAULT NOW())");
echo "Schema synced\n";

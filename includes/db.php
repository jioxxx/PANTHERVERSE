<?php
// ============================================================
// PANTHERVERSE — Database Connection
// Works on Laragon (direct config) AND Vercel (env vars)
// ============================================================

define('DB_TYPE',    getenv('DB_TYPE')    ?: 'mysql');
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'pantherverse_db');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_PORT',    getenv('DB_PORT')    ?: (DB_TYPE === 'mysql' ? '3306' : '5432'));
define('DB_CHARSET', 'utf8mb4');

$GLOBALS['_db'] = null;

function db(): PDO {
    if ($GLOBALS['_db']) return $GLOBALS['_db'];
    try {
        if (DB_TYPE === 'pgsql') {
            $dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME;
        } else {
            $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        }
        $GLOBALS['_db'] = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        
        // MySQL specific optimizations
        if (DB_TYPE === 'mysql') {
            $GLOBALS['_db']->exec("SET sql_mode = ''");
        }
    } catch (PDOException $e) {
        $db_type_label = DB_TYPE === 'pgsql' ? 'PostgreSQL (Supabase)' : 'MySQL (Laragon)';
        die('<div style="font-family:monospace;padding:30px;background:#0e0720;color:#f4a623;min-height:100vh;">
        <div style="max-width:500px;margin:80px auto;background:#1a0e38;border:2px solid rgba(124,58,237,0.4);border-radius:12px;padding:28px;">
            <h2>⚠️ Database Connection Failed</h2>
            <p style="color:#a78bca;margin:12px 0;">Attempted to connect to <strong>' . $db_type_label . '</strong>.</p>
            <p style="color:#a78bca;margin:12px 0;">Check your Vercel Environment Variables or local Laragon settings.</p>
            <p style="color:#ef4444;font-size:0.82rem;background:#0e0720;padding:10px;border-radius:6px;">'.htmlspecialchars($e->getMessage()).'</p>
        </div>');
    }
    return $GLOBALS['_db'];
}

function db_row(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql); $stmt->execute($params);
    return $stmt->fetch() ?: null;
}
function db_rows(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql); $stmt->execute($params);
    return $stmt->fetchAll();
}
function db_count(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql); $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
function db_insert(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql); $stmt->execute($params);
    return (int)db()->lastInsertId();
}
function db_exec(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql); $stmt->execute($params);
    return $stmt->rowCount();
}

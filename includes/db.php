<?php
// ============================================================
// PANTHERVERSE — Database Connection
// Works on Laragon (direct config) AND Vercel (env vars)
// ============================================================

define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'pantherverse_db');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

$GLOBALS['_db'] = null;

function db(): PDO {
    if ($GLOBALS['_db']) return $GLOBALS['_db'];
    try {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $GLOBALS['_db'] = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        
        // Disable ONLY_FULL_GROUP_BY mode for compatibility
        $GLOBALS['_db']->exec("SET sql_mode = ''");
    } catch (PDOException $e) {
        die('<div style="font-family:monospace;padding:30px;background:#0e0720;color:#f4a623;min-height:100vh;">
        <div style="max-width:500px;margin:80px auto;background:#1a0e38;border:2px solid rgba(124,58,237,0.4);border-radius:12px;padding:28px;">
            <h2>⚠️ Database Connection Failed</h2>
            <p style="color:#a78bca;margin:12px 0;">Make sure MySQL is running in Laragon and <code>pantherverse_db</code> exists. Import <strong>pantherverse_db.sql</strong> via HeidiSQL.</p>
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

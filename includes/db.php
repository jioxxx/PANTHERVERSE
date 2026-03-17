<?php
// ============================================================
// PANTHERVERSE — Database Connection
// Works on Laragon (direct config) AND Vercel (env vars)
// ============================================================

define('DB_TYPE',    getenv('DB_TYPE')    ?: 'mysql');
define('DB_HOST',    getenv('DB_HOST')    ?: '127.0.0.1');
define('DB_NAME',    getenv('DB_NAME')    ?: 'pantherverse_db');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_PORT',    getenv('DB_PORT')    ?: (DB_TYPE === 'mysql' ? '3306' : '5432'));
define('DB_CHARSET', 'utf8mb4');

$GLOBALS['_db'] = null;

function db(): PDO {
    if ($GLOBALS['_db']) return $GLOBALS['_db'];
    try {
        $db_url = getenv('DATABASE_URL');
        if ($db_url) {
            $url = parse_url($db_url);
            $scheme = $url['scheme'] ?? '';
            if (in_array($scheme, ['postgres', 'postgresql'])) {
                $type = 'pgsql';
            } elseif ($scheme === 'mysql') {
                $type = 'mysql';
            } else {
                $type = DB_TYPE;
            }
            $host = $url['host'] ?? DB_HOST;
            $port = $url['port'] ?? ($type === 'mysql' ? '3306' : '5432');
            $user = $url['user'] ?? DB_USER;
            $pass = $url['pass'] ?? DB_PASS;
            $name = ltrim($url['path'] ?? '', '/');
            $query = $url['query'] ?? '';
            
            if ($type === 'pgsql') {
                $dsn = "pgsql:host=$host;port=$port;dbname=$name";
                if (strpos($query, 'sslmode') === false) {
                    $dsn .= ";sslmode=require";
                }
                
                // Supabase/Neon Special Handling: 
                // If using a pooler host, the username MUST be 'username.project-id'
                if (strpos($host, 'supabase') !== false && strpos($user, '.') === false) {
                    // Try to extract project ID from host if possible (e.g. db.abc.supabase.co)
                    if (preg_match('/(?:db\.|^)([a-z0-9]{20})\.supabase/', $host, $matches)) {
                        $user .= '.' . $matches[1];
                    }
                }

                // Append any other query parameters (like options=project=...)
                if ($query) {
                    parse_str($query, $query_params);
                    foreach ($query_params as $k => $v) {
                        if ($k !== 'sslmode') $dsn .= ";$k=$v";
                    }
                }
            } else {
                $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=".DB_CHARSET;
            }
            $db_user = $user;
            $db_pass = $pass;
        } else {
            $db_user = DB_USER;
            $db_pass = DB_PASS;
            if (DB_TYPE === 'pgsql') {
                $dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";sslmode=require";
                // Apply same Supabase logic for direct constants
                if (strpos(DB_HOST, 'supabase') !== false && strpos($db_user, '.') === false) {
                    if (preg_match('/(?:db\.|^)([a-z0-9]{20})\.supabase/', DB_HOST, $matches)) {
                        $db_user .= '.' . $matches[1];
                    }
                }
            } else {
                $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            }
        }

        $GLOBALS['_db'] = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        
        // MySQL specific optimizations
        if (DB_TYPE === 'mysql') {
            $GLOBALS['_db']->exec("SET sql_mode = ''");
        }
    } catch (PDOException $e) {
        $db_type_label = DB_TYPE === 'pgsql' ? 'PostgreSQL (Supabase/Neon)' : 'MySQL (Laragon)';
        $safe_dsn = isset($dsn) ? preg_replace('/:.*@/', ':***@', $dsn) : 'Unknown';
        $safe_user = $db_user ?? 'None';
        
        die('<div style="font-family:monospace;padding:30px;background:#0e0720;color:#f4a623;min-height:100vh;">
        <div style="max-width:600px;margin:80px auto;background:#1a0e38;border:2px solid rgba(124,58,237,0.4);border-radius:12px;padding:28px;">
            <h2>⚠️ Database Connection Failed (v5)</h2>
            <p style="color:#a78bca;margin:12px 0;">Attempted to connect to <strong>' . $db_type_label . '</strong>.</p>
            
            <div style="background:#0e0720;padding:15px;border-radius:6px;margin:15px 0;border:1px solid rgba(255,255,255,0.1);">
                <p style="margin:0 0 5px 0;font-size:0.75rem;color:#888;">Sanitized DSN:</p>
                <code style="color:#fff;word-break:break-all;">' . htmlspecialchars($safe_dsn) . '</code>
                <p style="margin:15px 0 5px 0;font-size:0.75rem;color:#888;">Active Username:</p>
                <code style="color:#f4a623;font-weight:bold;">' . htmlspecialchars($safe_user) . '</code>
            </div>

            <p style="color:#ef4444;font-size:0.82rem;background:#0e0720;padding:15px;border-radius:6px;border-left:4px solid #ef4444;margin-top:20px;">
                <strong>Error Message:</strong><br>'.htmlspecialchars($e->getMessage()).'
            </p>
            
            <div style="margin-top:25px;border-top:1px solid rgba(255,255,255,0.1);padding-top:15px;font-size:0.85rem;color:#a78bca;">
                <p><strong>💡 Crucial Fix for Supabase:</strong></p>
                <p>Your error <code>Tenant or user not found</code> means the username is incorrect for the Pooler.</p>
                <ol style="padding-left:20px;">
                    <li>Go to Vercel Settings -> Environment Variables</li>
                    <li>If you use <code>DB_USER</code>, change it to <code>postgres.erxqnsjcmiomcbgizjvp</code></li>
                    <li>If you use <code>DATABASE_URL</code>, ensure the username part is <code>postgres.erxqnsjcmiomcbgizjvp</code></li>
                </ol>
            </div>
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

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
        $env_source = $db_url ? 'DATABASE_URL' : 'Individual Variables';
        
        if ($db_url) {
            $raw_url = $db_url;
            $scheme = parse_url($raw_url, PHP_URL_SCHEME);
            $user = parse_url($raw_url, PHP_URL_USER);
            $pass = parse_url($raw_url, PHP_URL_PASS);
            $host = parse_url($raw_url, PHP_URL_HOST);
            $port = parse_url($raw_url, PHP_URL_PORT);
            $path = parse_url($raw_url, PHP_URL_PATH);
            $query = parse_url($raw_url, PHP_URL_QUERY);
            
            $type = in_array($scheme, ['postgres', 'postgresql']) ? 'pgsql' : ($scheme === 'mysql' ? 'mysql' : DB_TYPE);
            $name = ltrim($path ?? '', '/');

            // Force port 6543 for Supabase poolers (Direct 5432 often fails on Vercel)
            if ($type === 'pgsql' && strpos($host, 'pooler.supabase') !== false) {
                $port = 6543;
            }
            
            $port = $port ?: ($type === 'mysql' ? '3306' : '5432');

            // Supabase Project ID auto-fix
            if ($type === 'pgsql' && strpos($host, 'supabase') !== false && strpos($user, '.') === false) {
                if (preg_match('/(?:db\.|^)([a-z0-9]{20})\.supabase/', $host, $matches)) {
                    $user .= '.' . $matches[1];
                }
            }

            if ($type === 'pgsql') {
                $dsn = "pgsql:host=$host;port=$port;dbname=$name";
                if (!$query || strpos($query, 'sslmode') === false) {
                    $dsn .= ";sslmode=require";
                }
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
            $db_host = DB_HOST;
            $db_port = DB_PORT;

            if (DB_TYPE === 'pgsql') {
                // Force port 6543 for fallbacks too
                if (strpos($db_host, 'pooler.supabase') !== false) {
                    $db_port = 6543;
                }
                
                if (strpos($db_host, 'supabase') !== false && strpos($db_user, '.') === false) {
                    if (preg_match('/(?:db\.|^)([a-z0-9]{20})\.supabase/', $db_host, $matches)) {
                        $db_user .= '.' . $matches[1];
                    }
                }
                $dsn = "pgsql:host=$db_host;port=$db_port;dbname=".DB_NAME.";sslmode=require";
            } else {
                $dsn = "mysql:host=$db_host;port=$db_port;dbname=".DB_NAME.";charset=".DB_CHARSET;
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
        $db_type_label = $type ?? (DB_TYPE === 'pgsql' ? 'pgsql' : 'mysql');
        $safe_dsn = isset($dsn) ? preg_replace('/:.*@/', ':***@', $dsn) : 'Unknown';
        
        die('<div style="font-family:monospace;padding:30px;background:#0e0720;color:#f4a623;min-height:100vh;display:flex;align-items:center;justify-content:center;">
        <div style="max-width:650px;background:#1a0e38;border:2px solid rgba(124,58,237,0.4);border-radius:16px;padding:35px;box-shadow:0 20px 50px rgba(0,0,0,0.5);">
            <h2 style="margin-top:0;display:flex;align-items:center;gap:10px;color:#fff;">
                <span style="font-size:1.5rem;">⚠️</span> Connection Failed (v7)
            </h2>
            
            <p style="color:#a78bca;font-size:0.8rem;margin-bottom:20px;">Source: <strong>' . $env_source . '</strong></p>

            <div style="background:#0e0720;padding:20px;border-radius:10px;margin:20px 0;border:1px solid rgba(255,255,255,0.05);">
                <div style="margin-bottom:15px;">
                    <p style="margin:0 0 5px 0;font-size:0.7rem;color:#7c3aed;text-transform:uppercase;letter-spacing:1px;font-weight:bold;">Attempted DSN</p>
                    <code style="color:#fff;word-break:break-all;font-size:0.85rem;">' . htmlspecialchars($safe_dsn) . '</code>
                </div>
                <div>
                    <p style="margin:0 0 5px 0;font-size:0.7rem;color:#7c3aed;text-transform:uppercase;letter-spacing:1px;font-weight:bold;">Attempted User</p>
                    <code style="color:#f4a623;font-size:1rem;font-weight:bold;">' . htmlspecialchars($db_user ?? 'None') . '</code>
                </div>
            </div>

            <div style="background:rgba(239,68,68,0.1);padding:15px;border-radius:8px;border-left:4px solid #ef4444;margin-top:20px;color:#fca5a5;font-size:0.9rem;">
                <strong>Driver Error:</strong><br>'.htmlspecialchars($e->getMessage()).'
            </div>
            
            <div style="margin-top:30px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.1);font-size:0.85rem;color:#a78bca;line-height:1.6;">
                <p><strong>💡 Final Fix:</strong> If you see <code>port=5432</code> above, your Vercel Environment variables are overriding the code. Change <code>DB_PORT</code> to <code>6543</code> in Vercel Settings.</p>
            </div>
        </div></div>');
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

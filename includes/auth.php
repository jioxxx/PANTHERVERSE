<?php
// ============================================================
// Auth helpers
// ============================================================

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $user = null;
    if (!$user) {
        $bool_true = $GLOBALS['_sql_true'];
        $user = db_row("SELECT * FROM users WHERE id = ? AND is_active = $bool_true", [$_SESSION['user_id']]);
        if (!$user) { session_destroy(); return null; }
    }
    return $user;
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_user_role(): string {
    return current_user()['role'] ?? 'guest';
}

function current_user_year_level(): ?int {
    $user = current_user();
    return $user ? ($user['role'] === 'student' ? (int)$user['year_level'] : null) : null;
}

function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!in_array(current_user_role(), $roles)) {
        die('<div style="padding:40px;text-align:center;font-family:monospace;background:#1a0a2e;color:#f4a623;">⛔ Access denied.</div>');
    }
}

function is_staff(): bool {
    return in_array(current_user_role(), ['instructor','admin']);
}

// ============================================================
// Utility helpers
// ============================================================

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="'.csrf_token().'">';
}

function csrf_check(): void {
    if (($_POST['csrf'] ?? '') !== csrf_token()) {
        die('Invalid CSRF token.');
    }
}

function flash(string $key, string $msg = ''): string {
    if ($msg) {
        $_SESSION['flash'][$key] = $msg;
        return '';
    }
    $val = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $val;
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff/60).'m ago';
    if ($diff < 86400)  return floor($diff/3600).'h ago';
    if ($diff < 604800) return floor($diff/86400).'d ago';
    return date('M j, Y', strtotime($datetime));
}

function slugify(string $text): string {
    $text = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    return $text ?: 'post';
}

function add_reputation(int $user_id, int $amount, string $reason): void {
    db_exec("UPDATE users SET reputation = reputation + ? WHERE id = ?", [$amount, $user_id]);
    db_insert("INSERT INTO reputation_logs (user_id, amount, reason, created_at) VALUES (?, ?, ?, NOW())",
        [$user_id, $amount, $reason]);
}

function send_notification(int $user_id, string $type, array $data): void {
    if ($user_id === current_user_id()) return;
    db_insert("INSERT INTO notifications (user_id, type, data, created_at) VALUES (?, ?, ?, NOW())",
        [$user_id, $type, json_encode($data)]);
}

function unread_notifications(): int {
    if (!is_logged_in()) return 0;
    return db_count("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL",
        [current_user_id()]);
}

function avatar_url(string $username): string {
    static $cache = [];
    if (!isset($cache[$username])) {
        try {
            $u = db_row("SELECT profile_photo FROM users WHERE username=?", [$username]);
            $cache[$username] = $u['profile_photo'] ?? null;
        } catch (Exception $e) {
            $cache[$username] = null;
        }
    }
    
    if (!empty($cache[$username])) {
        return BASE_PATH . "/assets/uploads/profiles/" . rawurlencode($cache[$username]);
    }

    $color = '5B21B6';
    $text  = urlencode(strtoupper(substr($username, 0, 1)));
    return "https://ui-avatars.com/api/?name={$text}&background={$color}&color=F4A623&size=80&bold=true";
}

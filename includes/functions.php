<?php
// ============================================================
// PANTHERVERSE — Additional Utility Functions
// Note: Core helpers (auth, CSRF, etc.) are in auth.php
// Note: Database helpers are in db.php
// ============================================================

// ─────────────────────────────────────────────────────────────
// LIKE FUNCTIONS
// ─────────────────────────────────────────────────────────────

function is_liked(int $user_id, int $item_id, string $type): bool {
    return db_count("SELECT 1 FROM likes WHERE user_id = ? AND liked_id = ? AND liked_type = ?",
        [$user_id, $item_id, $type]) > 0;
}

function get_like_count(int $item_id, string $type): int {
    return db_count("SELECT like_count FROM " . match ($type) {
        'forum_post' => 'forum_posts',
        'resource' => 'resources',
        'project' => 'projects',
        'announcement' => 'announcements',
        'question' => 'questions',
        'answer' => 'answers',
    } . " WHERE id = ?", [$item_id]);
}

function toggle_like(int $item_id, string $type): bool {
    require_login();
    $user_id = current_user_id();
    
    $table_map = [
        'forum_post' => 'forum_posts',
        'resource' => 'resources',
        'project' => 'projects',
        'announcement' => 'announcements',
        'question' => 'questions',
        'answer' => 'answers'
    ];
    $table = $table_map[$type];
    
    if (is_liked($user_id, $item_id, $type)) {
        // Unlike
        db_exec("DELETE FROM likes WHERE user_id = ? AND liked_id = ? AND liked_type = ?",
            [$user_id, $item_id, $type]);
        db_exec("UPDATE $table SET like_count = like_count - 1 WHERE id = ?", [$item_id]);
        return false;
    } else {
        // Like
        db_insert("INSERT INTO likes (user_id, liked_id, liked_type, created_at) VALUES (?, ?, ?, NOW())",
            [$user_id, $item_id, $type]);
        db_exec("UPDATE $table SET like_count = like_count + 1 WHERE id = ?", [$item_id]);
        
        // Notify content owner
        $content = db_row("SELECT user_id FROM $table WHERE id = ?", [$item_id]);
        if ($content && $content['user_id'] != $user_id) {
            $user = current_user();
            send_notification($content['user_id'], 'content_liked', [
                'liker_id' => $user_id,
                'liker_name' => $user['username'],
                'content_type' => $type,
                'content_id' => $item_id
            ]);
        }
        return true;
    }
}

function get_most_liked_posts(string $type, int $limit = 10): array {
    $table_map = [
        'forum_post' => 'forum_posts',
        'resource' => 'resources',
        'project' => 'projects',
        'announcement' => 'announcements',
        'question' => 'questions',
        'answer' => 'answers'
    ];
    $table = $table_map[$type] ?? 'forum_posts';
    
    return db_rows("
        SELECT * FROM $table 
        WHERE deleted_at IS NULL AND COALESCE(like_count, 0) > 0
        ORDER BY COALESCE(like_count, 0) DESC, created_at DESC
        LIMIT ?
    ", [$limit]);
}

function paginate(int $total, int $per_page = 20, string $base_url = ''): array {
    $page = (int) ($_GET['page'] ?? 1);
    $page = max(1, $page);
    $total_pages = ceil($total / $per_page);
    $offset = ($page - 1) * $per_page;
    
    return [
        'page' => $page,
        'per_page' => $per_page,
        'total' => $total,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'limit' => $per_page,
        'base_url' => $base_url
    ];
}

// ─────────────────────────────────────────────────────────────
// BOOKMARKS FUNCTIONS
// ─────────────────────────────────────────────────────────────

function is_bookmarked(int $user_id, $item_id, string $type): bool {
    return db_count("SELECT 1 FROM bookmarks WHERE user_id = ? AND bookmarkable_id = ? AND bookmarkable_type = ?",
        [$user_id, $item_id, $type]) > 0;
}

function toggle_bookmark($item_id, string $type): bool {
    require_login();
    $user_id = current_user_id();
    
    if (is_bookmarked($user_id, $item_id, $type)) {
        db_exec("DELETE FROM bookmarks WHERE user_id = ? AND bookmarkable_id = ? AND bookmarkable_type = ?",
            [$user_id, $item_id, $type]);
        return false;
    } else {
        db_insert("INSERT INTO bookmarks (user_id, bookmarkable_id, bookmarkable_type, created_at) VALUES (?, ?, ?, NOW())",
            [$user_id, $item_id, $type]);
        return true;
    }
}

function get_user_bookmarks(int $user_id, string $type = '', int $limit = 20, int $offset = 0): array {
    $where = $type ? "AND b.bookmarkable_type = '$type'" : "";
    return db_rows("
        SELECT b.*, 
            CASE 
                WHEN b.bookmarkable_type = 'question' THEN q.title
                WHEN b.bookmarkable_type = 'resource' THEN r.title
                WHEN b.bookmarkable_type = 'forum_post' THEN fp.title
                WHEN b.bookmarkable_type = 'project' THEN p.title
            END as item_title,
            CASE 
                WHEN b.bookmarkable_type = 'question' THEN q.slug
                WHEN b.bookmarkable_type = 'resource' THEN r.file_path
                WHEN b.bookmarkable_type = 'forum_post' THEN fp.id
                WHEN b.bookmarkable_type = 'project' THEN p.id
            END as item_slug
        FROM bookmarks b
        LEFT JOIN questions q ON b.bookmarkable_id = q.id AND b.bookmarkable_type = 'question'
        LEFT JOIN resources r ON b.bookmarkable_id = r.id AND b.bookmarkable_type = 'resource'
        LEFT JOIN forum_posts fp ON b.bookmarkable_id = fp.id AND b.bookmarkable_type = 'forum_post'
        LEFT JOIN projects p ON b.bookmarkable_id = p.id AND b.bookmarkable_type = 'project'
        WHERE b.user_id = ? $where
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?
    ", [$user_id, $limit, $offset]);
}

// ─────────────────────────────────────────────────────────────
// FOLLOW SYSTEM FUNCTIONS
// ─────────────────────────────────────────────────────────────

function is_following(int $user_id, int $target_id): bool {
    return db_count("SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = ?", [$user_id, $target_id]) > 0;
}

function is_following_tag(int $user_id, int $tag_id): bool {
    return db_count("SELECT 1 FROM tag_follows WHERE user_id = ? AND tag_id = ?", [$user_id, $tag_id]) > 0;
}

function toggle_follow(int $target_id): bool {
    require_login();
    $user_id = current_user_id();
    
    if ($user_id === $target_id) return false;
    
    if (is_following($user_id, $target_id)) {
        db_exec("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?", [$user_id, $target_id]);
        return false;
    } else {
        db_insert("INSERT INTO user_follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())", [$user_id, $target_id]);
        send_notification($target_id, 'new_follower', ['follower_id' => $user_id, 'follower_name' => current_user()['username']]);
        return true;
    }
}

function toggle_follow_tag(int $tag_id): bool {
    require_login();
    $user_id = current_user_id();
    
    if (is_following_tag($user_id, $tag_id)) {
        db_exec("DELETE FROM tag_follows WHERE user_id = ? AND tag_id = ?", [$user_id, $tag_id]);
        return false;
    } else {
        db_insert("INSERT INTO tag_follows (user_id, tag_id, created_at) VALUES (?, ?, NOW())", [$user_id, $tag_id]);
        return true;
    }
}

function get_followers(int $user_id, int $limit = 20, int $offset = 0): array {
    return db_rows("
        SELECT u.*, uf.created_at as followed_at
        FROM user_follows uf
        JOIN users u ON uf.follower_id = u.id
        WHERE uf.following_id = ?
        ORDER BY uf.created_at DESC
        LIMIT ? OFFSET ?
    ", [$user_id, $limit, $offset]);
}

function get_following(int $user_id, int $limit = 20, int $offset = 0): array {
    return db_rows("
        SELECT u.*, uf.created_at as followed_at
        FROM user_follows uf
        JOIN users u ON uf.following_id = u.id
        WHERE uf.follower_id = ?
        ORDER BY uf.created_at DESC
        LIMIT ? OFFSET ?
    ", [$user_id, $limit, $offset]);
}

function get_follower_count(int $user_id): int {
    return db_count("SELECT COUNT(*) FROM user_follows WHERE following_id = ?", [$user_id]);
}

function get_following_count(int $user_id): int {
    return db_count("SELECT COUNT(*) FROM user_follows WHERE follower_id = ?", [$user_id]);
}

// ─────────────────────────────────────────────────────────────
// NOTIFICATION PREFERENCES
// ─────────────────────────────────────────────────────────────

function get_notification_preferences(int $user_id): array {
    $pref = db_row("SELECT * FROM notification_preferences WHERE user_id = ?", [$user_id]);
    if (!$pref) {
        db_insert("INSERT INTO notification_preferences (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())", [$user_id]);
        $pref = db_row("SELECT * FROM notification_preferences WHERE user_id = ?", [$user_id]);
    }
    return $pref;
}

function update_notification_preferences(int $user_id, array $data): void {
    $fields = [];
    $values = [];
    foreach ($data as $key => $value) {
        $fields[] = "`$key` = ?";
        $values[] = $value;
    }
    $values[] = $user_id;
    db_exec("UPDATE notification_preferences SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE user_id = ?", $values);
}

// ─────────────────────────────────────────────────────────────
// USER PREFERENCES (Theme, Dark Mode)
// ─────────────────────────────────────────────────────────────

function get_user_preferences(int $user_id): array {
    $pref = db_row("SELECT * FROM user_preferences WHERE user_id = ?", [$user_id]);
    if (!$pref) {
        db_insert("INSERT INTO user_preferences (user_id, theme, language, created_at, updated_at) VALUES (?, 'dark', 'en', NOW(), NOW())", [$user_id]);
        $pref = db_row("SELECT * FROM user_preferences WHERE user_id = ?", [$user_id]);
    }
    return $pref;
}

function update_user_preferences(int $user_id, array $data): void {
    $fields = [];
    $values = [];
    foreach ($data as $key => $value) {
        $fields[] = "`$key` = ?";
        $values[] = $value;
    }
    $values[] = $user_id;
    db_exec("UPDATE user_preferences SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE user_id = ?", $values);
}

// ─────────────────────────────────────────────────────────────
// QUESTION DRAFTS
// ─────────────────────────────────────────────────────────────

function save_question_draft(int $user_id, string $title, string $body, ?string $tags = null): int {
    $existing = db_row("SELECT id FROM question_drafts WHERE user_id = ?", [$user_id]);
    if ($existing) {
        db_exec("UPDATE question_drafts SET title = ?, body = ?, tags = ?, updated_at = NOW() WHERE user_id = ?",
            [$title, $body, $tags, $user_id]);
        return $existing['id'];
    } else {
        return db_insert("INSERT INTO question_drafts (user_id, title, body, tags, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
            [$user_id, $title, $body, $tags]);
    }
}

function get_question_draft(int $user_id): ?array {
    return db_row("SELECT * FROM question_drafts WHERE user_id = ?", [$user_id]);
}

function delete_question_draft(int $user_id): void {
    db_exec("DELETE FROM question_drafts WHERE user_id = ?", [$user_id]);
}

// ─────────────────────────────────────────────────────────────
// SEARCH FUNCTIONS
// ─────────────────────────────────────────────────────────────

function search_all(string $query, string $type = '', int $limit = 20): array {
    $query = trim($query);
    if (empty($query)) return [];
    
    $results = [];
    $q = "%$query%";
    
    if (empty($type) || $type === 'questions') {
        $results['questions'] = db_rows("
            SELECT q.id, q.title, q.body, q.vote_count, q.view_count, q.is_solved, u.username, 
                   (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) as answer_count
            FROM questions q
            JOIN users u ON q.user_id = u.id
            WHERE q.deleted_at IS NULL AND (q.title LIKE ? OR q.body LIKE ?)
            ORDER BY q.vote_count DESC, q.created_at DESC
            LIMIT ?
        ", [$q, $q, $limit]);
    }
    
    if (empty($type) || $type === 'users') {
        $results['users'] = db_rows("
            SELECT id, username, name, role, reputation, bio
            FROM users
            WHERE is_active = 1 AND (username LIKE ? OR name LIKE ? OR email LIKE ?)
            ORDER BY reputation DESC
            LIMIT ?
        ", [$q, $q, $q, $limit]);
    }
    
    if (empty($type) || $type === 'resources') {
        $results['resources'] = db_rows("
            SELECT r.id, r.title, r.description, r.file_type, r.download_count, u.username
            FROM resources r
            JOIN users u ON r.user_id = u.id
            WHERE r.deleted_at IS NULL AND (r.title LIKE ? OR r.description LIKE ?)
            ORDER BY r.download_count DESC
            LIMIT ?
        ", [$q, $q, $limit]);
    }
    
    if (empty($type) || $type === 'forum_posts') {
        $results['forum_posts'] = db_rows("
            SELECT fp.id, fp.title, fp.body, fp.view_count, fp.reply_count, u.username, fc.name as category_name
            FROM forum_posts fp
            JOIN users u ON fp.user_id = u.id
            JOIN forum_categories fc ON fp.category_id = fc.id
            WHERE fp.deleted_at IS NULL AND (fp.title LIKE ? OR fp.body LIKE ?)
            ORDER BY fp.view_count DESC
            LIMIT ?
        ", [$q, $q, $limit]);
    }
    
    if (empty($type) || $type === 'tags') {
        $results['tags'] = db_rows("
            SELECT id, name, slug, description, usage_count
            FROM tags
            WHERE name LIKE ? OR description LIKE ?
            ORDER BY usage_count DESC
            LIMIT ?
        ", [$q, $q, $limit]);
    }
    
    return $results;
}

// ─────────────────────────────────────────────────────────────
// USER DIRECTORY
// ─────────────────────────────────────────────────────────────

function get_users_directory(?string $role = null, ?int $campus_id = null, ?int $program_id = null, string $search = '', int $limit = 20, int $offset = 0): array {
    $conditions = ["u.is_active = 1"];
    $params = [];
    
    if ($role) {
        $conditions[] = "u.role = ?";
        $params[] = $role;
    }
    if ($campus_id) {
        $conditions[] = "u.campus_id = ?";
        $params[] = $campus_id;
    }
    if ($program_id) {
        $conditions[] = "u.program_id = ?";
        $params[] = $program_id;
    }
    if ($search) {
        $conditions[] = "(u.username LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $s = "%$search%";
        $params = array_merge($params, [$s, $s, $s]);
    }
    
    $where = implode(" AND ", $conditions);
    
    $users = db_rows("
        SELECT u.*, c.name as campus_name, p.code as program_code,
               (SELECT COUNT(*) FROM questions q WHERE q.user_id = u.id AND q.deleted_at IS NULL) as question_count,
               (SELECT COUNT(*) FROM answers a WHERE a.user_id = u.id AND a.deleted_at IS NULL) as answer_count
        FROM users u
        LEFT JOIN campuses c ON u.campus_id = c.id
        LEFT JOIN programs p ON u.program_id = p.id
        WHERE $where
        ORDER BY u.reputation DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]));
    
    $total = db_count("SELECT COUNT(*) FROM users u WHERE " . $where, $params);
    
    return ['users' => $users, 'total' => $total];
}

// ─────────────────────────────────────────────────────────────
// ACTIVITY LOG
// ─────────────────────────────────────────────────────────────

function log_activity(string $action, ?int $user_id = null, ?string $entity_type = null, ?int $entity_id = null, array $metadata = []): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    db_insert("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, metadata, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
        [$user_id, $action, $entity_type, $entity_id, json_encode($metadata), $ip, $agent]);
}

// ─────────────────────────────────────────────────────────────
// ANALYTICS HELPERS
// ─────────────────────────────────────────────────────────────

function get_analytics_summary(): array {
    $today = date('Y-m-d');
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $month_ago = date('Y-m-d', strtotime('-30 days'));
    
    return [
        'total_users' => db_count("SELECT COUNT(*) FROM users WHERE is_active = 1"),
        'total_questions' => db_count("SELECT COUNT(*) FROM questions WHERE deleted_at IS NULL"),
        'total_answers' => db_count("SELECT COUNT(*) FROM answers WHERE deleted_at IS NULL"),
        'total_resources' => db_count("SELECT COUNT(*) FROM resources WHERE deleted_at IS NULL"),
        'total_forum_posts' => db_count("SELECT COUNT(*) FROM forum_posts WHERE deleted_at IS NULL"),
        'new_users_today' => db_count("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?", [$today]),
        'new_users_week' => db_count("SELECT COUNT(*) FROM users WHERE DATE(created_at) >= ?", [$week_ago]),
        'new_users_month' => db_count("SELECT COUNT(*) FROM users WHERE DATE(created_at) >= ?", [$month_ago]),
        'questions_today' => db_count("SELECT COUNT(*) FROM questions WHERE DATE(created_at) = ?", [$today]),
        'answers_today' => db_count("SELECT COUNT(*) FROM answers WHERE DATE(created_at) = ?", [$today]),
        'active_users_weekly' => db_count("SELECT COUNT(DISTINCT user_id) FROM activity_logs WHERE created_at >= ?", [$week_ago]),
    ];
}

function get_popular_content(string $type = 'questions', int $days = 7, int $limit = 10): array {
    $since = date('Y-m-d', strtotime("-{$days} days"));
    
    switch ($type) {
        case 'questions':
            return db_rows("
                SELECT q.*, u.username,
                       (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) as answer_count
                FROM questions q
                JOIN users u ON q.user_id = u.id
                WHERE q.deleted_at IS NULL AND q.created_at >= ?
                ORDER BY q.view_count DESC, q.vote_count DESC
                LIMIT ?
            ", [$since, $limit]);
            
        case 'answers':
            return db_rows("
                SELECT a.*, q.title as question_title, u.username
                FROM answers a
                JOIN questions q ON a.question_id = q.id
                JOIN users u ON a.user_id = u.id
                WHERE a.deleted_at IS NULL AND a.created_at >= ?
                ORDER BY a.vote_count DESC
                LIMIT ?
            ", [$since, $limit]);
            
        case 'resources':
            return db_rows("
                SELECT r.*, u.username
                FROM resources r
                JOIN users u ON r.user_id = u.id
                WHERE r.deleted_at IS NULL AND r.created_at >= ?
                ORDER BY r.download_count DESC
                LIMIT ?
            ", [$since, $limit]);
            
        default:
            return [];
    }
}

// ─────────────────────────────────────────────────────────────
// CONTENT FLAGS
// ─────────────────────────────────────────────────────────────

function flag_content(string $type, int $content_id, string $reason, ?string $description = null): void {
    require_login();
    db_insert("INSERT INTO content_flags (flagger_id, content_type, content_id, reason, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
        [current_user_id(), $type, $content_id, $reason, $description]);
}

function get_pending_flags(): array {
    return db_rows("
        SELECT f.*, u.username as flagger_name,
               CASE 
                   WHEN f.content_type = 'question' THEN (SELECT title FROM questions WHERE id = f.content_id)
                   WHEN f.content_type = 'answer' THEN (SELECT body FROM answers WHERE id = f.content_id)
                   WHEN f.content_type = 'forum_post' THEN (SELECT title FROM forum_posts WHERE id = f.content_id)
               END as content_title
        FROM content_flags f
        JOIN users u ON f.flagger_id = u.id
        WHERE f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
}

// ─────────────────────────────────────────────────────────────
// API TOKEN HELPERS
// ─────────────────────────────────────────────────────────────

function generate_api_token(): string {
    return bin2hex(random_bytes(32));
}

function create_api_token(int $user_id, string $name, string $permissions = 'read'): array {
    $token = generate_api_token();
    $id = db_insert("INSERT INTO api_tokens (user_id, name, token, permissions, created_at) VALUES (?, ?, ?, ?, NOW())",
        [$user_id, $name, $token, $permissions]);
    return ['id' => $id, 'token' => $token];
}

function validate_api_token(string $token): ?array {
    $token_data = db_row("
        SELECT t.*, u.username, u.email, u.role
        FROM api_tokens t
        JOIN users u ON t.user_id = u.id
        WHERE t.token = ? AND (t.expires_at IS NULL OR t.expires_at > NOW())
    ", [$token]);
    
    if ($token_data) {
        db_exec("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?", [$token_data['id']]);
    }
    
    return $token_data;
}

function get_user_api_tokens(int $user_id): array {
    return db_rows("SELECT id, name, permissions, last_used_at, expires_at, created_at FROM api_tokens WHERE user_id = ? ORDER BY created_at DESC", [$user_id]);
}

function delete_api_token(int $token_id, int $user_id): void {
    db_exec("DELETE FROM api_tokens WHERE id = ? AND user_id = ?", [$token_id, $user_id]);
}

// ─────────────────────────────────────────────────────────────
// TAG WIKI FUNCTIONS
// ─────────────────────────────────────────────────────────────

function get_tag_wiki(int $tag_id): ?array {
    return db_row("SELECT tw.*, u.username FROM tag_wikis tw JOIN users u ON tw.user_id = u.id WHERE tw.tag_id = ? AND tw.is_approved = 1", [$tag_id]);
}

function save_tag_wiki(int $tag_id, int $user_id, string $content): void {
    db_exec("INSERT INTO tag_wikis (tag_id, user_id, content, is_approved, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW())
             ON DUPLICATE KEY UPDATE content = ?, user_id = ?, updated_at = NOW()",
        [$tag_id, $user_id, $content, $content, $user_id]);
}

// ─────────────────────────────────────────────────────────────
// HELPER: UUID GENERATOR
// ─────────────────────────────────────────────────────────────

function uuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        random_int(0, 0xffff), random_int(0, 0xffff),
        random_int(0, 0xffff), random_int(0, 0x0fff) | 0x4000,
        random_int(0, 0x3fff) | 0x8000, random_int(0, 0xffff),
        random_int(0, 0xffff), random_int(0, 0xffff)
    );
}

// ─────────────────────────────────────────────────────────────
// DARK MODE / THEME HELPERS
// ─────────────────────────────────────────────────────────────

function get_current_theme(): string {
    if (!is_logged_in()) {
        return 'dark';
    }
    $prefs = get_user_preferences(current_user_id());
    $theme = $prefs['theme'] ?? 'dark';
    
    if ($theme === 'system') {
        return 'dark';
    }
    return $theme;
}

// ─────────────────────────────────────────────────────────────
// ADDITIONAL UTILITY FUNCTIONS
// ─────────────────────────────────────────────────────────────

function format_bytes(int $bytes, int $decimals = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, $decimals) . ' ' . $units[$i];
}

function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

function is_online(?string $last_seen_at): bool {
    if (!$last_seen_at) return false;
    return strtotime($last_seen_at) > strtotime('-5 minutes');
}

function get_popular_tags(int $limit = 16): array {
    return db_rows("SELECT * FROM tags ORDER BY usage_count DESC LIMIT ?", [$limit]);
}

function get_hot_questions(int $limit = 5): array {
    return db_rows("
        SELECT q.id, q.title, q.vote_count, q.view_count
        FROM questions q
        WHERE q.deleted_at IS NULL AND q.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY (q.view_count + q.vote_count * 3) DESC
        LIMIT ?
    ", [$limit]);
}


<?php
// api/v1.php - REST API v1 Endpoint
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get API token from header
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';

if (preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
    $token = $matches[1];
}

// Validate token
$user = null;
if ($token) {
    $token_data = validate_api_token($token);
    if ($token_data) {
        $user = $token_data;
    }
}

// Parse request
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Correctly strip the full path to the script including BASE_PATH
$script_path = BASE_PATH . '/api/v1.php';
$request_uri = str_replace($script_path, '', $request_uri);
$parts = array_filter(explode('/', $request_uri));
$parts = array_values($parts);


$resource = $parts[0] ?? 'questions';
$id = $parts[1] ?? null;

// Helper function for JSON response
function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function require_auth() {
    global $user;
    if (!$user) {
        json_response(['error' => 'Authentication required'], 401);
    }
}

// Route requests
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($resource) {
            case 'questions':
                $page = (int)($_GET['page'] ?? 1);
                $limit = min((int)($_GET['limit'] ?? 20), 100);
                $offset = ($page - 1) * $limit;
                
                $questions = db_rows("
                    SELECT q.*, u.username, 
                           (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) as answer_count
                    FROM questions q
                    JOIN users u ON q.user_id = u.id
                    WHERE q.deleted_at IS NULL
                    ORDER BY q.created_at DESC
                    LIMIT ? OFFSET ?
                ", [$limit, $offset]);
                
                $total = db_count("SELECT COUNT(*) FROM questions WHERE deleted_at IS NULL");
                
                json_response([
                    'data' => $questions,
                    'meta' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]);
                
            case 'question':
                if (!$id) json_response(['error' => 'Question ID required'], 400);
                
                $question = db_row("
                    SELECT q.*, u.username 
                    FROM questions q
                    JOIN users u ON q.user_id = u.id
                    WHERE q.id = ? AND q.deleted_at IS NULL
                ", [$id]);
                
                if (!$question) json_response(['error' => 'Question not found'], 404);
                
                // Get answers
                $answers = db_rows("
                    SELECT a.*, u.username
                    FROM answers a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.question_id = ? AND a.deleted_at IS NULL
                    ORDER BY a.is_accepted DESC, a.vote_count DESC
                ", [$id]);
                
                json_response([
                    'data' => $question,
                    'answers' => $answers
                ]);
                
            case 'users':
                $page = (int)($_GET['page'] ?? 1);
                $limit = min((int)($_GET['limit'] ?? 20), 100);
                $offset = ($page - 1) * $limit;
                
                $users = db_rows("
                    SELECT id, username, name, role, reputation, bio, created_at
                    FROM users WHERE is_active = 1
                    ORDER BY reputation DESC
                    LIMIT ? OFFSET ?
                ", [$limit, $offset]);
                
                json_response(['data' => $users]);
                
            case 'user':
                if (!$id) json_response(['error' => 'User ID required'], 400);
                
                $profile = db_row("
                    SELECT id, username, name, role, reputation, bio, campus_id, program_id, created_at
                    FROM users WHERE id = ? AND is_active = 1
                ", [$id]);
                
                if (!$profile) json_response(['error' => 'User not found'], 404);
                
                // Get stats
                $profile['stats'] = [
                    'questions' => db_count("SELECT COUNT(*) FROM questions WHERE user_id = ? AND deleted_at IS NULL", [$id]),
                    'answers' => db_count("SELECT COUNT(*) FROM answers WHERE user_id = ? AND deleted_at IS NULL", [$id]),
                    'resources' => db_count("SELECT COUNT(*) FROM resources WHERE user_id = ? AND deleted_at IS NULL", [$id])
                ];
                
                json_response(['data' => $profile]);
                
            case 'tags':
                $tags = db_rows("SELECT * FROM tags ORDER BY usage_count DESC LIMIT 50");
                json_response(['data' => $tags]);
                
            case 'resources':
                $resources = db_rows("
                    SELECT r.*, u.username
                    FROM resources r
                    JOIN users u ON r.user_id = u.id
                    WHERE r.deleted_at IS NULL
                    ORDER BY r.download_count DESC
                    LIMIT 20
                ");
                json_response(['data' => $resources]);
                
            case 'me':
                require_auth();
                json_response([
                    'data' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ]);
                
            default:
                json_response(['error' => 'Resource not found'], 404);
        }
        break;
        
    case 'POST':
        // Create new token for authentication demo
        if ($resource === 'auth' && isset($_POST['email']) && isset($_POST['password'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];
            
            $user = db_row("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
            
            if ($user && password_verify($password, $user['password'])) {
                $token_info = create_api_token($user['id'], 'API Access');
                json_response([
                    'success' => true,
                    'token' => $token_info['token'],
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ]
                ]);
            } else {
                json_response(['error' => 'Invalid credentials'], 401);
            }
        }
        
        json_response(['error' => 'Invalid request'], 400);
        break;
        
    default:
        json_response(['error' => 'Method not allowed'], 405);
}


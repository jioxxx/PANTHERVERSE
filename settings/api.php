<?php
// settings/api.php - API Tokens Management
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
$user = current_user();
$uid = current_user_id();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    if (isset($_POST['create_token'])) {
        $name = trim($_POST['name'] ?? 'My API Token');
        $permissions = $_POST['permissions'] ?? 'read';
        
        if (empty($name)) {
            $error = 'Please provide a token name';
        } else {
            $token_data = create_api_token($uid, $name, $permissions);
            $success = 'Token created! Copy it now: <code style="background:var(--bg3);padding:4px 8px;border-radius:4px;word-break:break-all;">' . $token_data['token'] . '</code>';
            log_activity('api_token_created', $uid);
        }
    }
    
    if (isset($_POST['delete_token'])) {
        $token_id = (int)$_POST['token_id'];
        delete_api_token($token_id, $uid);
        $success = 'Token deleted';
        log_activity('api_token_deleted', $uid);
    }
}

// Get user's tokens
$tokens = get_user_api_tokens($uid);

$page_title = 'API Access';
require_once '../includes/header.php';
?>

<div class="page-wrap">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
        <a href="settings.php" class="btn-ghost" style="padding:8px 12px;">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <div>
            <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.5rem;font-weight:700;">🔑 API Access</h1>
            <p style="font-size:0.82rem;color:var(--text-d);">Generate tokens to access PANTHERVERSE from external apps</p>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- Create Token -->
    <div class="card" style="margin-bottom:24px;">
        <div class="card-head">
            <span class="card-title">➕ Create New Token</span>
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                    <div style="flex:1;min-width:200px;">
                        <label style="font-size:0.8rem;color:var(--text-m);margin-bottom:4px;display:block;">Token Name</label>
                        <input type="text" name="name" placeholder="e.g., My Mobile App" required>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:var(--text-m);margin-bottom:4px;display:block;">Permissions</label>
                        <select name="permissions" style="width:auto;min-width:140px;">
                            <option value="read">Read Only</option>
                            <option value="write">Read & Write</option>
                        </select>
                    </div>
                    <button type="submit" name="create_token" value="1" class="btn-gold">Generate Token</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Tokens -->
    <div class="card">
        <div class="card-head">
            <span class="card-title">🔐 Your API Tokens</span>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if ($tokens): ?>
            <table class="pv-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Permissions</th>
                        <th>Last Used</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $t): ?>
                    <tr>
                        <td><?= e($t['name']) ?></td>
                        <td><span class="badge-pill badge-purple"><?= e($t['permissions']) ?></span></td>
                        <td><?= $t['last_used_at'] ? time_ago($t['last_used_at']) : 'Never' ?></td>
                        <td><?= time_ago($t['created_at']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="token_id" value="<?= $t['id'] ?>">
                                <button type="submit" name="delete_token" value="1" class="btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="padding:32px;text-align:center;color:var(--text-d);">
                No API tokens yet. Create one above to get started.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- API Documentation -->
    <div class="card" style="margin-top:24px;">
        <div class="card-head">
            <span class="card-title">📚 API Documentation</span>
        </div>
        <div class="card-body">
            <p style="font-size:0.9rem;color:var(--text-m);margin-bottom:12px;">
                Use your API token to access PANTHERVERSE data programmatically.
            </p>
            <div style="background:var(--bg3);padding:16px;border-radius:8px;margin-bottom:12px;">
                <div style="font-family:monospace;color:var(--gold);font-size:0.85rem;">
                    Authorization: Bearer YOUR_API_TOKEN
                </div>
            </div>
            <p style="font-size:0.8rem;color:var(--text-d);">
                <strong>Base URL:</strong> <?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_PATH ?>/api/v1.php<br>
                <strong>Example:</strong> GET /api/v1.php/questions
            </p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>


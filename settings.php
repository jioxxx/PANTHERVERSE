<?php
// settings.php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();


$user = current_user();
$error = ''; $ok = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $name       = trim($_POST['name'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');
    $campus_id  = (int)($_POST['campus_id'] ?? 0);
    $program_id = (int)($_POST['program_id'] ?? 0);
    $new_pw     = $_POST['new_password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    if (!$name) { $error = 'Name is required.'; }
    elseif ($new_pw && strlen($new_pw) < 8) { $error = 'Password must be at least 8 characters.'; }
    elseif ($new_pw && $new_pw !== $confirm) { $error = 'Passwords do not match.'; }
    else {
        $profile_photo = $user['profile_photo'];
        $cover_photo   = $user['cover_photo'] ?? null;

        if (!empty($_POST['cropped_profile_photo'])) {
            $data = $_POST['cropped_profile_photo'];
            list($type, $data) = explode(';', $data);
            list(, $data)      = explode(',', $data);
            $data = base64_decode($data);
            $pf_name = 'pf_' . current_user_id() . '_' . time() . '.png';
            if (file_put_contents('assets/uploads/profiles/' . $pf_name, $data)) {
                $profile_photo = $pf_name;
            }
        } elseif (!empty($_FILES['profile_photo']['name'])) {
            $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $pf_name = 'pf_' . current_user_id() . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], 'assets/uploads/profiles/' . $pf_name)) {
                $profile_photo = $pf_name;
            }
        }

        if (!empty($_POST['cropped_cover_photo'])) {
            $data = $_POST['cropped_cover_photo'];
            list($type, $data) = explode(';', $data);
            list(, $data)      = explode(',', $data);
            $data = base64_decode($data);
            $cp_name = 'cp_' . current_user_id() . '_' . time() . '.png';
            if (file_put_contents('assets/uploads/covers/' . $cp_name, $data)) {
                $cover_photo = $cp_name;
            }
        } elseif (!empty($_FILES['cover_photo']['name'])) {
            $ext = pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION);
            $cp_name = 'cp_' . current_user_id() . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['cover_photo']['tmp_name'], 'assets/uploads/covers/' . $cp_name)) {
                $cover_photo = $cp_name;
            }
        }

        $params = [$name, $bio ?: null, $campus_id ?: null, $program_id ?: null, $profile_photo, $cover_photo, $user['id']];
        db_exec("UPDATE users SET name=?, bio=?, campus_id=?, program_id=?, profile_photo=?, cover_photo=?, updated_at=NOW() WHERE id=?", $params);
        if ($new_pw) db_exec("UPDATE users SET password=? WHERE id=?", [password_hash($new_pw, PASSWORD_BCRYPT, ['cost'=>12]), $user['id']]);
        $ok   = 'Profile updated successfully!';
        $user = db_row("SELECT * FROM users WHERE id=?", [$user['id']]);
    }
}

$bool_true  = $GLOBALS['_sql_true'];
$campuses   = db_rows("SELECT id, name FROM campuses WHERE is_active=$bool_true ORDER BY name");
$programs   = db_rows("SELECT id, name, code FROM programs ORDER BY name");
$page_title = 'Settings';
require_once 'includes/header.php';
?>
<style>
.settings-layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 24px;
    max-width: 960px;
    margin: 0 auto;
}
@media(max-width:768px) { .settings-layout { grid-template-columns: 1fr; } }

.settings-sidebar {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.settings-nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 14px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-m);
    text-decoration: none;
    transition: all 0.15s;
    border: 1px solid transparent;
}
.settings-nav-link:hover { background: var(--surface2); color: var(--text); border-color: var(--border); }
.settings-nav-link.active { background: rgba(124,58,237,0.12); color: var(--purple-l); border-color: rgba(124,58,237,0.3); }
.settings-nav-link i { font-size: 1rem; width: 18px; text-align: center; }

.settings-user-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px;
    text-align: center;
    margin-bottom: 16px;
}
.settings-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    border: 2px solid var(--purple);
    object-fit: cover;
    margin: 0 auto 10px;
    display: block;
    box-shadow: 0 0 20px rgba(124,58,237,0.3);
}
.settings-username { font-family: 'Rajdhani',sans-serif; font-weight: 700; font-size: 1.1rem; color: var(--text); }
.settings-role { font-size: 0.78rem; color: var(--text-d); margin-top: 2px; }

/* Toggle switches */
.toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid rgba(124,58,237,0.08);
}
.toggle-row:last-child { border-bottom: none; }
.toggle-label { font-size: 0.88rem; color: var(--text-m); }
.toggle-switch { position: relative; width: 44px; height: 24px; cursor: pointer; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute; inset: 0;
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: 24px;
    transition: all 0.2s;
}
.toggle-slider::before {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    left: 2px; top: 2px;
    background: var(--text-d);
    border-radius: 50%;
    transition: all 0.2s;
}
.toggle-switch input:checked + .toggle-slider { background: rgba(124,58,237,0.3); border-color: var(--purple); }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); background: var(--purple-l); box-shadow: 0 0 8px rgba(168,85,247,0.5); }

/* Photo upload areas */
.photo-upload-area {
    border: 2px dashed var(--border);
    border-radius: 10px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}
.photo-upload-area:hover { border-color: var(--purple); background: rgba(124,58,237,0.04); }
.photo-upload-area input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.photo-upload-area i { font-size: 1.5rem; color: var(--text-d); display: block; margin-bottom: 6px; }
.photo-upload-area span { font-size: 0.8rem; color: var(--text-d); }
</style>

<div class="page-wrap" style="padding-top:8px;">
    <!-- Page Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;">
        <div>
            <h1 style="font-size:1.8rem;font-weight:700;background:linear-gradient(135deg,#fff,var(--purple-l));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">
                Account Settings
            </h1>
            <p style="color:var(--text-d);font-size:0.88rem;margin-top:4px;">Manage your profile, security and preferences</p>
        </div>
        <a href="profile.php?u=<?= urlencode($user['username']) ?>" class="btn-ghost btn-sm">
            <i class="bi bi-person"></i> View Profile
        </a>
    </div>

    <div class="settings-layout">
        <!-- Sidebar -->
        <div>
            <div class="settings-user-card">
                <img src="<?= avatar_url($user['username']) ?>" class="settings-avatar" alt="avatar">
                <div class="settings-username"><?= e($user['name']) ?></div>
                <div class="settings-role">@<?= e($user['username']) ?> · <?= ucfirst($user['role']) ?></div>
            </div>

            <a href="settings.php" class="settings-nav-link active">
                <i class="bi bi-person-gear"></i> Profile
            </a>
            <a href="settings/notifications.php" class="settings-nav-link">
                <i class="bi bi-bell"></i> Notifications
            </a>
            <a href="settings/theme.php" class="settings-nav-link">
                <i class="bi bi-palette"></i> Appearance
            </a>
            <a href="settings/api.php" class="settings-nav-link">
                <i class="bi bi-key"></i> API Access
            </a>
            <a href="bookmarks.php" class="settings-nav-link">
                <i class="bi bi-bookmark"></i> Bookmarks
            </a>
            <hr style="margin:12px 0;">
            <a href="profile.php?u=<?= urlencode($user['username']) ?>" class="settings-nav-link">
                <i class="bi bi-eye"></i> View Public Profile
            </a>
        </div>

        <!-- Main Content -->
        <div>
            <?php if($error): ?><div class="alert alert-error"><i class="bi bi-exclamation-circle"></i><?= e($error) ?></div><?php endif; ?>
            <?php if($ok):    ?><div class="alert alert-success"><i class="bi bi-check-circle"></i><?= e($ok) ?></div><?php endif; ?>

            <!-- Photo Upload -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-head">
                    <span class="card-title"><i class="bi bi-camera" style="color:var(--purple-l);margin-right:8px;"></i>Photos</span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-m);margin-bottom:8px;">
                                Profile Picture <span id="pf-status" style="color:var(--green);font-size:0.78rem;display:none;">✓ Ready</span>
                            </label>
                            <div class="photo-upload-area">
                                <input type="file" id="profile_photo_input" accept="image/jpeg,image/png,image/gif">
                                <i class="bi bi-person-circle"></i>
                                <span>Click to upload<br>JPG, PNG or GIF</span>
                            </div>
                        </div>
                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text-m);margin-bottom:8px;">
                                Cover Photo <span id="cp-status" style="color:var(--green);font-size:0.78rem;display:none;">✓ Ready</span>
                            </label>
                            <div class="photo-upload-area">
                                <input type="file" id="cover_photo_input" accept="image/jpeg,image/png,image/gif">
                                <i class="bi bi-image"></i>
                                <span>Click to upload<br>Wide format recommended</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-head">
                    <span class="card-title"><i class="bi bi-person" style="color:var(--purple-l);margin-right:8px;"></i>Profile Information</span>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="profileForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="cropped_profile_photo" id="cropped_profile_photo">
                        <input type="hidden" name="cropped_cover_photo" id="cropped_cover_photo">

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?= e($user['name']) ?>" required placeholder="Your full name">
                        </div>
                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" rows="3" placeholder="Tell the community about yourself..."><?= e($user['bio']??'') ?></textarea>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Campus</label>
                                <select name="campus_id">
                                    <option value="">— Select Campus —</option>
                                    <?php foreach($campuses as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $user['campus_id']==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Program</label>
                                <select name="program_id">
                                    <option value="">— Select Program —</option>
                                    <?php foreach($programs as $p): ?>
                                    <option value="<?= $p['id'] ?>" <?= $user['program_id']==$p['id']?'selected':'' ?>><?= e($p['code']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <hr style="margin:24px 0 20px;">

                        <div style="margin-bottom:8px;">
                            <div style="font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1rem;color:var(--text);margin-bottom:4px;">
                                <i class="bi bi-shield-lock" style="color:var(--gold);margin-right:6px;"></i>Change Password
                            </div>
                            <p style="font-size:0.8rem;color:var(--text-d);">Leave blank to keep your current password</p>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>New Password</label>
                                <input type="password" name="new_password" placeholder="Min. 8 characters">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" placeholder="Repeat new password">
                            </div>
                        </div>

                        <div style="display:flex;gap:12px;align-items:center;">
                            <button type="submit" class="btn-gold" id="saveBtn">
                                <i class="bi bi-check-lg"></i> Save Changes
                            </button>
                            <a href="profile.php?u=<?= urlencode($user['username']) ?>" class="btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cropper Modal -->
<div id="cropperModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.88);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(8px);">
    <div style="background:var(--surface);padding:28px;border-radius:16px;width:90%;max-width:720px;box-shadow:0 20px 60px rgba(0,0,0,0.8);border:1px solid var(--border);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <h3 style="font-family:'Rajdhani',sans-serif;font-size:1.3rem;font-weight:700;">Crop Image</h3>
            <button onclick="closeCropper()" style="background:none;border:none;color:var(--text-m);font-size:1.4rem;cursor:pointer;padding:4px;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div style="width:100%;max-height:460px;overflow:hidden;margin-bottom:20px;background:#000;border-radius:8px;">
            <img id="cropperImage" src="" style="max-width:100%;display:block;">
        </div>
        <div style="display:flex;justify-content:flex-end;gap:12px;">
            <button type="button" class="btn-ghost" onclick="closeCropper()">Cancel</button>
            <button type="button" class="btn-gold" onclick="cropImage()"><i class="bi bi-scissors"></i> Save Crop</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
let cropper;
let currentCropType = '';

document.getElementById('profile_photo_input').addEventListener('change', function(e) {
    initCropper(e.target, 'profile');
});
document.getElementById('cover_photo_input').addEventListener('change', function(e) {
    initCropper(e.target, 'cover');
});

function initCropper(input, type) {
    if (input.files && input.files[0]) {
        currentCropType = type;
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('cropperImage').src = e.target.result;
            document.getElementById('cropperModal').style.display = 'flex';
            if (cropper) { cropper.destroy(); }
            cropper = new Cropper(document.getElementById('cropperImage'), {
                aspectRatio: type === 'profile' ? 1/1 : 21/6,
                viewMode: 1,
                autoCropArea: 1,
                background: false
            });
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function closeCropper() {
    document.getElementById('cropperModal').style.display = 'none';
    if (cropper) { cropper.destroy(); }
    if (currentCropType === 'profile') document.getElementById('profile_photo_input').value = '';
    if (currentCropType === 'cover') document.getElementById('cover_photo_input').value = '';
}

function cropImage() {
    if (!cropper) return;
    const canvas = cropper.getCroppedCanvas({
        width: currentCropType === 'profile' ? 400 : 1200,
        height: currentCropType === 'profile' ? 400 : 343,
    });
    const base64 = canvas.toDataURL('image/png');
    if (currentCropType === 'profile') {
        document.getElementById('cropped_profile_photo').value = base64;
        document.getElementById('pf-status').style.display = 'inline';
    } else {
        document.getElementById('cropped_cover_photo').value = base64;
        document.getElementById('cp-status').style.display = 'inline';
    }
    document.getElementById('cropperModal').style.display = 'none';
    cropper.destroy();
}
</script>
<?php require_once 'includes/footer.php'; ?>

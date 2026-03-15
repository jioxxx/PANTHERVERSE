<?php
// settings.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$user = current_user();
$error = ''; $ok = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $name     = trim($_POST['name'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');
    $campus_id  = (int)($_POST['campus_id'] ?? 0);
    $program_id = (int)($_POST['program_id'] ?? 0);
    $new_pw   = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$name) { $error = 'Name is required.'; }
    elseif ($new_pw && strlen($new_pw) < 8) { $error = 'Password must be at least 8 characters.'; }
    elseif ($new_pw && $new_pw !== $confirm) { $error = 'Passwords do not match.'; }
    else {
        $profile_photo = $user['profile_photo'];
        $cover_photo = $user['cover_photo'] ?? null;

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
        if ($new_pw) db_exec("UPDATE users SET password=? WHERE id=?", [password_hash($new_pw,PASSWORD_BCRYPT,['cost'=>12]), $user['id']]);
        $ok = 'Profile updated successfully!';
        $user = db_row("SELECT * FROM users WHERE id=?", [$user['id']]);
    }
}

$campuses = db_rows("SELECT id, name FROM campuses WHERE is_active=1 ORDER BY name");
$programs = db_rows("SELECT id, name, code FROM programs ORDER BY name");
$page_title = 'Settings';
require_once 'includes/header.php';
?>
<div class="page-wrap" style="max-width:600px;">
  <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:20px;">⚙️ Settings</h1>
  <?php if($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if($ok):    ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
  <div class="card">
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        
        <input type="hidden" name="cropped_profile_photo" id="cropped_profile_photo">
        <input type="hidden" name="cropped_cover_photo" id="cropped_cover_photo">
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div class="form-group" style="margin-bottom:0;">
            <label>Profile Picture <span id="pf-status" style="color:var(--gold);font-size:0.8rem;display:none;">(Cropped & Ready!)</span></label>
            <input type="file" id="profile_photo_input" accept="image/jpeg,image/png,image/gif" style="width:100%; border:1px solid var(--border); border-radius:6px; padding:6px; background:var(--bg); color:var(--text-m); font-size:0.85rem;">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label>Cover Photo <span id="cp-status" style="color:var(--gold);font-size:0.8rem;display:none;">(Cropped & Ready!)</span></label>
            <input type="file" id="cover_photo_input" accept="image/jpeg,image/png,image/gif" style="width:100%; border:1px solid var(--border); border-radius:6px; padding:6px; background:var(--bg); color:var(--text-m); font-size:0.85rem;">
          </div>
        </div>

        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="name" value="<?= e($user['name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Bio</label>
          <textarea name="bio" rows="3" placeholder="Tell others about yourself..."><?= e($user['bio']??'') ?></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group">
            <label>Campus</label>
            <select name="campus_id">
              <option value="">Select Campus</option>
              <?php foreach($campuses as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $user['campus_id']==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Program</label>
            <select name="program_id">
              <option value="">Select Program</option>
              <?php foreach($programs as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $user['program_id']==$p['id']?'selected':'' ?>><?= e($p['code']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <hr>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group">
            <label>New Password <span class="form-hint" style="display:inline;">(leave blank to keep)</span></label>
            <input type="password" name="new_password" placeholder="Min. 8 characters">
          </div>
          <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Repeat new password">
          </div>
        </div>
        <button type="submit" class="btn-gold">Save Changes</button>
        <a href="profile.php?u=<?= urlencode($user['username']) ?>" class="btn-ghost" style="margin-left:10px;">Cancel</a>
      </form>
    </div>
  </div>
</div>

<!-- Cropper Modal -->
<div id="cropperModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(5px);">
    <div style="background:var(--surface);padding:24px;border-radius:12px;width:90%;max-width:700px;box-shadow:0 10px 40px rgba(0,0,0,0.8); border:1px solid var(--border);">
        <h3 style="margin-top:0;font-family:'Rajdhani',sans-serif;font-size:1.3rem;">Adjust Your Image</h3>
        <div style="width:100%;max-height:500px;overflow:hidden;margin-bottom:20px;background:#000;border-radius:6px;">
            <img id="cropperImage" src="" style="max-width:100%;display:block;">
        </div>
        <div style="display:flex;justify-content:flex-end;gap:12px;">
            <button type="button" class="btn-ghost" onclick="closeCropper()">Cancel</button>
            <button type="button" class="btn-gold" onclick="cropImage()">Save Crop</button>
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
            
            const aspectRatio = type === 'profile' ? 1/1 : 21/6;
            cropper = new Cropper(document.getElementById('cropperImage'), {
                aspectRatio: aspectRatio,
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
    
    // Default to a medium quality jpeg if it's too big, or use png
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

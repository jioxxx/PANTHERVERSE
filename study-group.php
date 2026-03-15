<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$id    = (int)($_GET['id'] ?? 0);
$group = db_row("SELECT sg.*,u.username AS owner_username,u.name AS owner_name FROM study_groups sg JOIN users u ON sg.owner_id=u.id WHERE sg.id=?", [$id]);
if (!$group) { flash('error','Group not found.'); redirect('study-groups.php'); }
$is_public = 1; // Default to public if column doesn't exist

$is_member = is_logged_in() && !!db_row("SELECT 1 FROM study_group_members WHERE user_id=? AND group_id=?", [current_user_id(),$id]);
$my_role   = $is_member ? db_row("SELECT role FROM study_group_members WHERE user_id=? AND group_id=?", [current_user_id(),$id])['role'] : null;
$is_mod    = $my_role === 'moderator' || current_user_role() === 'admin';
$is_owner   = current_user_id() == $group['owner_id'];

$members = db_rows("SELECT u.id,u.name,u.username,u.reputation,u.last_seen_at,sgm.role AS group_role, sgm.joined_at FROM study_group_members sgm JOIN users u ON sgm.user_id=u.id WHERE sgm.group_id=?", [$id]);

$posts   = db_rows("SELECT sgp.*,u.username,u.name AS user_name FROM study_group_posts sgp JOIN users u ON sgp.user_id=u.id WHERE sgp.group_id=? AND sgp.deleted_at IS NULL ORDER BY sgp.created_at ASC", [$id]);

// Get online members (active in last 5 minutes)
$online_members = db_rows("SELECT user_id FROM study_group_members WHERE group_id=? AND user_id IN (SELECT id FROM users WHERE last_seen_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE))", [$id]);
$online_ids = array_column($online_members, 'user_id');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    
    // Send message
    if (isset($_POST['post_msg'])) {
        if (!$is_member) { flash('error','Join the group to chat.'); redirect("study-group.php?id=$id"); }
        $body = trim($_POST['body'] ?? '');
        if (strlen($body)<1) $error = 'Message cannot be empty.';
        else { 
            db_insert("INSERT INTO study_group_posts (group_id,user_id,body,created_at,updated_at) VALUES (?,?,?,NOW(),NOW())", [$id,current_user_id(),$body]); 
            redirect("study-group.php?id=$id"); 
        }
    }
    
    // Delete post
    if (isset($_POST['delete_post']) && ($is_mod || $is_owner)) {
        $post_id = (int)($_POST['post_id'] ?? 0);
        db_exec("UPDATE study_group_posts SET deleted_at=NOW() WHERE id=? AND group_id=?", [$post_id, $id]);
        $success = 'Message deleted.';
        redirect("study-group.php?id=$id");
    }
    
    // Leave group
    if (isset($_POST['leave'])) {
        if ($is_member && !$is_owner) {
            db_exec("DELETE FROM study_group_members WHERE user_id=? AND group_id=?", [current_user_id(),$id]);
            redirect('study-groups.php');
        }
    }
    
    // Update last seen
    if (is_logged_in()) {
        db_exec("UPDATE users SET last_seen_at = NOW() WHERE id = ?", [current_user_id()]);
    }
}

$member_count = count($members);
$page_title = e($group['name']);
require_once 'includes/header.php';
?>

<!-- FACEBOOK MESSENGER STYLE - FULL FEATURED -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap');

/* EMOJI PICKER */
.emoji-picker {
    position: absolute;
    bottom: 70px;
    left: 10px;
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 12px;
    padding: 10px;
    display: none;
    width: 300px;
    max-height: 250px;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    z-index: 1000;
}

.emoji-picker.active { display: block; }

.emoji-picker-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid #333;
}

.emoji-picker-header h5 {
    margin: 0;
    color: #fff;
    font-size: 0.9rem;
}

.emoji-picker-close {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 1.2rem;
}

.emoji-categories {
    display: flex;
    gap: 4px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.emoji-cat-btn {
    background: #2a2a2a;
    border: none;
    border-radius: 6px;
    padding: 6px 8px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.2s;
}

.emoji-cat-btn:hover, .emoji-cat-btn.active {
    background: #7c3aed;
}

.emoji-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 4px;
}

.emoji-btn {
    background: none;
    border: none;
    font-size: 1.4rem;
    padding: 4px;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.15s;
}

.emoji-btn:hover {
    background: #2a2a2a;
    transform: scale(1.2);
}

/* FILE ATTACHMENT */
.attachment-panel {
    position: absolute;
    bottom: 70px;
    left: 60px;
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 12px;
    padding: 15px;
    display: none;
    width: 320px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    z-index: 1000;
}

.attachment-panel.active { display: block; }

.attachment-options {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.attachment-option {
    flex: 1;
    min-width: 80px;
    background: #2a2a2a;
    border: none;
    border-radius: 10px;
    padding: 15px;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
    color: #fff;
}

.attachment-option:hover {
    background: #7c3aed;
    transform: translateY(-2px);
}

.attachment-option i {
    font-size: 1.5rem;
    margin-bottom: 5px;
    display: block;
}

.attachment-option span {
    font-size: 0.75rem;
}

.attachment-preview {
    margin-top: 15px;
    padding: 10px;
    background: #0d0d0d;
    border-radius: 8px;
    display: none;
}

.attachment-preview.active { display: block; }

.attachment-preview img {
    max-width: 100%;
    max-height: 150px;
    border-radius: 8px;
}

.attachment-preview-file {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #2a2a2a;
    border-radius: 8px;
}

.attachment-preview-file i {
    font-size: 1.5rem;
    color: #7c3aed;
}

.attachment-preview-file .file-info {
    flex: 1;
    overflow: hidden;
}

.attachment-preview-file .file-name {
    color: #fff;
    font-size: 0.85rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.attachment-preview-file .file-size {
    color: #666;
    font-size: 0.75rem;
}

.attachment-remove {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 1.2rem;
}

* { box-sizing: border-box; }

.messenger { 
    display:flex; 
    height: calc(100vh - 90px); 
    margin: 10px; 
    background: #0d0d0d; 
    border-radius: 12px; 
    overflow: hidden; 
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    font-family: 'Nunito', sans-serif;
}

/* LEFT SIDEBAR */
.mess-sidebar { 
    width: 70px; 
    background: #000; 
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    padding: 15px 0; 
    gap: 8px; 
    border-right: 1px solid #222;
}

.mess-icon { 
    width: 50px; 
    height: 50px; 
    border-radius: 16px; 
    background: #1a1a1a; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 1.4rem; 
    cursor: pointer; 
    transition: all 0.2s;
    text-decoration: none;
    color: #666;
}

.mess-icon:hover { 
    background: #2a2a2a; 
    transform: scale(1.05);
}

.mess-icon.active { 
    background: linear-gradient(135deg, #7c3aed, #a855f7);
    color: #fff;
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
}

.mess-user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    margin-top: auto;
    cursor: pointer;
    position: relative;
}

.mess-user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.mess-user-status {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    background: #22c55e;
    border-radius: 50%;
    border: 3px solid #000;
}

/* CHAT AREA */
.mess-chat { 
    flex: 1; 
    display: flex; 
    flex-direction: column; 
    background: #0d0d0d;
    min-width: 0;
}

.mess-header { 
    padding: 12px 20px; 
    background: linear-gradient(135deg, #1a1a2e, #16213e); 
    display: flex; 
    align-items: center; 
    gap: 14px; 
    border-bottom: 1px solid #222;
    flex-shrink: 0;
}

.mess-back { 
    font-size: 1.3rem; 
    color: #888; 
    text-decoration: none; 
    padding: 10px; 
    border-radius: 10px;
    transition: all 0.2s;
}

.mess-back:hover { 
    background: #2a2a2a; 
    color: #fff; 
}

.mess-avatar { 
    width: 44px; 
    height: 44px; 
    border-radius: 14px; 
    background: linear-gradient(135deg, #7c3aed, #a855f7); 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: #fff; 
    font-weight: 700; 
    font-size: 0.95rem; 
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
    flex-shrink: 0;
}

.mess-title { 
    flex: 1; 
    min-width: 0;
}

.mess-title h3 { 
    margin: 0; 
    color: #fff; 
    font-size: 1.1rem; 
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mess-title span { 
    color: #22c55e; 
    font-size: 0.8rem; 
    display: flex;
    align-items: center;
    gap: 5px;
}

.mess-title span::before {
    content: '';
    width: 8px;
    height: 8px;
    background: #22c55e;
    border-radius: 50%;
}

.mess-header-actions { 
    display: flex; 
    gap: 8px; 
}

.mess-header-btn { 
    width: 40px; 
    height: 40px; 
    border-radius: 12px; 
    background: #1a1a1a; 
    border: none; 
    color: #888; 
    font-size: 1.1rem; 
    cursor: pointer; 
    display: flex; 
    align-items: center; 
    justify-content: center;
    transition: all 0.2s;
}

.mess-header-btn:hover { 
    background: #2a2a2a; 
    color: #fff; 
}

/* MESSAGES */
.mess-messages { 
    flex: 1; 
    overflow-y: auto; 
    padding: 20px; 
    display: flex; 
    flex-direction: column; 
    gap: 3px;
    background: linear-gradient(180deg, #0d0d0d 0%, #111 50%, #0d0d0d 100%);
}

.mess-date-divider {
    text-align: center;
    margin: 20px 0;
    color: #555;
    font-size: 0.75rem;
    position: relative;
}

.mess-date-divider::before,
.mess-date-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 30%;
    height: 1px;
    background: #222;
}

.mess-date-divider::before { left: 0; }
.mess-date-divider::after { right: 0; }

.mess-msg { 
    display: flex; 
    gap: 10px; 
    max-width: 65%;
    animation: msgIn 0.2s ease-out;
}

@keyframes msgIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.mess-msg.mine { 
    flex-direction: row-reverse; 
    align-self: flex-end; 
}

.mess-msg-avatar { 
    width: 36px; 
    height: 36px; 
    border-radius: 50%; 
    background: linear-gradient(135deg, #7c3aed, #a855f7); 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: #fff; 
    font-size: 0.7rem; 
    font-weight: 700; 
    flex-shrink: 0;
    position: relative;
    cursor: pointer;
}

.mess-msg.mine .mess-msg-avatar { display: none; }

.mess-msg-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.mess-msg-name { 
    font-size: 0.75rem; 
    color: #888; 
    margin-left: 12px;
    font-weight: 600;
}

.mess-msg.mine .mess-msg-name { 
    text-align: right;
    margin-left: 0;
    margin-right: 12px;
}

.mess-msg-bubble { 
    padding: 12px 16px; 
    border-radius: 20px; 
    background: #1a1a1a; 
    color: #fff; 
    font-size: 0.95rem; 
    line-height: 1.5; 
    word-wrap: break-word;
    position: relative;
    cursor: pointer;
    transition: all 0.2s;
}

.mess-msg-bubble:hover {
    background: #252525;
}

.mess-msg.mine .mess-msg-bubble { 
    background: linear-gradient(135deg, #7c3aed, #a855f7); 
    border-radius: 20px 20px 4px 20px; 
}

.mess-msg-bubble:hover .msg-actions {
    opacity: 1;
}

.msg-actions {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #222;
    border-radius: 20px;
    padding: 4px;
    display: flex;
    gap: 2px;
    opacity: 0;
    transition: opacity 0.2s;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.msg-action-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: none;
    border: none;
    color: #888;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.msg-action-btn:hover {
    background: #333;
    color: #fff;
}

.mess-msg-info { 
    font-size: 0.7rem; 
    color: #444; 
    margin-left: 12px; 
    display: flex;
    align-items: center;
    gap: 8px;
}

.mess-msg.mine .mess-msg-info { 
    text-align: right;
    margin-left: 0;
    margin-right: 12px;
    justify-content: flex-end;
}

.msg-status {
    font-size: 0.8rem;
}

.msg-status.seen {
    color: #22c55e;
}

/* INPUT AREA */
.mess-input { 
    padding: 16px 20px; 
    background: linear-gradient(135deg, #1a1a2e, #16213e); 
    display: flex; 
    gap: 12px; 
    align-items: center; 
    border-top: 1px solid #222;
    flex-shrink: 0;
}

.mess-input-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #1a1a1a;
    border: none;
    color: #888;
    font-size: 1.2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.mess-input-btn:hover {
    background: #2a2a2a;
    color: #fff;
    transform: scale(1.05);
}

.mess-input fieldset {
    flex: 1;
    background: #1a1a1a;
    border: 2px solid #222;
    border-radius: 24px;
    padding: 2px;
    display: flex;
    align-items: center;
    transition: border-color 0.2s;
}

.mess-input fieldset:focus-within {
    border-color: #7c3aed;
}

.mess-input input { 
    flex: 1; 
    background: transparent;
    border: none; 
    padding: 12px 16px; 
    color: #fff; 
    font-size: 0.95rem; 
    outline: none;
}

.mess-input input::placeholder { 
    color: #555; 
}

.mess-send-btn { 
    width: 44px; 
    height: 44px; 
    border-radius: 50%; 
    background: linear-gradient(135deg, #7c3aed, #a855f7); 
    border: none; 
    color: #fff; 
    font-size: 1.1rem; 
    cursor: pointer; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
    transition: all 0.2s;
    flex-shrink: 0;
}

.mess-send-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 20px rgba(124, 58, 237, 0.5);
}

/* MEMBERS PANEL */
.mess-members { 
    width: 300px; 
    background: #0a0a0a; 
    border-left: 1px solid #222; 
    display: none;
    flex-direction: column;
}

@media(min-width: 1000px) { 
    .mess-members { display: flex; } 
}

.mess-members-header { 
    padding: 20px; 
    border-bottom: 1px solid #222;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.mess-members-header h4 { 
    margin: 0; 
    color: #fff; 
    font-size: 1rem;
    font-weight: 700;
}

.mess-members-search {
    padding: 12px 16px;
    border-bottom: 1px solid #222;
}

.mess-members-search input {
    width: 100%;
    background: #1a1a1a;
    border: none;
    border-radius: 10px;
    padding: 10px 14px;
    color: #fff;
    font-size: 0.85rem;
    outline: none;
}

.mess-members-search input::placeholder {
    color: #555;
}

.mess-members-list { 
    flex: 1; 
    overflow-y: auto; 
    padding: 10px;
}

.mess-member { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    padding: 12px; 
    border-radius: 12px; 
    cursor: pointer; 
    transition: all 0.2s;
    margin-bottom: 4px;
}

.mess-member:hover { 
    background: #1a1a1a; 
}

.mess-member-avatar { 
    width: 44px; 
    height: 44px; 
    border-radius: 50%; 
    background: linear-gradient(135deg, #7c3aed, #a855f7); 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: #fff; 
    font-size: 0.8rem; 
    font-weight: 700; 
    position: relative;
    flex-shrink: 0;
}

.mess-member-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.mess-member-status { 
    position: absolute; 
    bottom: 0; 
    right: 0; 
    width: 14px; 
    height: 14px; 
    background: #666; 
    border-radius: 50%; 
    border: 3px solid #0a0a0a;
}

.mess-member-status.online { 
    background: #22c55e;
    box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
}

.mess-member-info { 
    flex: 1; 
    min-width: 0;
}

.mess-member-name { 
    color: #fff; 
    font-size: 0.9rem; 
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mess-member-role { 
    color: #666; 
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.mess-member-badge {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #000;
    font-size: 0.6rem;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 700;
}

.mess-member-badge.mod {
    background: linear-gradient(135deg, #7c3aed, #a855f7);
    color: #fff;
}

/* GROUP INFO SECTION */
.mess-group-info {
    padding: 20px;
    border-top: 1px solid #222;
}

.mess-group-info-title {
    color: #fff;
    font-size: 0.85rem;
    font-weight: 700;
    margin-bottom: 12px;
}

.mess-group-stat {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    color: #888;
    font-size: 0.85rem;
}

.mess-group-stat i {
    color: #7c3aed;
}

.leave-group-btn {
    width: 100%;
    padding: 12px;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 10px;
    color: #ef4444;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 10px;
}

.leave-group-btn:hover {
    background: rgba(239, 68, 68, 0.2);
}

/* TYPING INDICATOR */
.typing-indicator {
    display: none;
    padding: 10px 20px;
    color: #666;
    font-size: 0.8rem;
    font-style: italic;
}

.typing-indicator.active {
    display: block;
}

.typing-dots {
    display: inline-flex;
    gap: 4px;
    margin-left: 8px;
}

.typing-dots span {
    width: 6px;
    height: 6px;
    background: #666;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-6px); }
}

/* EMPTY STATE */
.mess-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #555;
    text-align: center;
    padding: 40px;
}

.mess-empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.mess-empty h3 {
    color: #fff;
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.mess-empty p {
    font-size: 0.9rem;
    max-width: 300px;
}

/* SCROLLBAR */
.mess-messages::-webkit-scrollbar,
.mess-members-list::-webkit-scrollbar {
    width: 6px;
}

.mess-messages::-webkit-scrollbar-track,
.mess-members-list::-webkit-scrollbar-track {
    background: transparent;
}

.mess-messages::-webkit-scrollbar-thumb,
.mess-members-list::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 3px;
}

.mess-messages::-webkit-scrollbar-thumb:hover,
.mess-members-list::-webkit-scrollbar-thumb:hover {
    background: #444;
}

/* RESPONSIVE */
@media(max-width: 768px) {
    .mess-members { display: none; }
    .mess-sidebar { width: 60px; }
    .mess-icon { width: 44px; height: 44px; font-size: 1.2rem; }
    .mess-msg { max-width: 85%; }
}
</style>

<div class="messenger">
    <!-- LEFT SIDEBAR -->
    <div class="mess-sidebar">
        <a href="study-groups.php" class="mess-icon active" title="Groups">💬</a>
        <a href="messages.php" class="mess-icon" title="Messages">✉️</a>
        <a href="notifications.php" class="mess-icon" title="Notifications">🔔</a>
        <a href="calendar.php" class="mess-icon" title="Calendar">📅</a>
        <a href="resources.php" class="mess-icon" title="Resources">📁</a>
        
        <?php if(is_logged_in()): ?>
        <a href="profile.php?u=<?= urlencode(current_user()['username']) ?>" class="mess-user-avatar" title="Profile">
            <?= strtoupper(substr(current_user()['username'], 0, 2)) ?>
            <div class="mess-user-status"></div>
        </a>
        <?php endif; ?>
    </div>

    <!-- CHAT AREA -->
    <div class="mess-chat">
        <div class="mess-header">
            <a href="study-groups.php" class="mess-back">←</a>
            <div class="mess-avatar"><?= strtoupper(substr($group['name'], 0, 2)) ?></div>
            <div class="mess-title">
                <h3><?= e($group['name']) ?></h3>
<span><?= $member_count ?> members • <?= $is_public ? 'Public' : 'Private' ?></span>
            </div>
            <div class="mess-header-actions">
                <button class="mess-header-btn" title="Video Call">📹</button>
                <button class="mess-header-btn" title="Voice Call">📞</button>
                <button class="mess-header-btn" title="Search">🔍</button>
            </div>
        </div>

        <!-- Typing Indicator (placeholder for real-time) -->
        <div class="typing-indicator" id="typingIndicator">
            Someone is typing<span class="typing-dots"><span></span><span></span><span></span></span>
        </div>

        <div class="mess-messages" id="chatBox">
            <?php if(empty($posts)): ?>
            <div class="mess-empty">
                <div class="mess-empty-icon">💬</div>
                <h3>Welcome to <?= e($group['name']) ?>!</h3>
                <p>Start the conversation by sending a message below. This group has <?= $member_count ?> members.</p>
            </div>
            <?php endif; ?>
            
            <?php 
            $last_date = '';
            foreach($posts as $p): 
                $msg_date = date('Y-m-d', strtotime($p['created_at']));
                if ($msg_date != $last_date) {
                    $last_date = $msg_date;
                    $date_label = date('Y-m-d') == $msg_date ? 'Today' : (date('Y-m-d', strtotime('-1 day')) == $msg_date ? 'Yesterday' : date('F j, Y', strtotime($msg_date)));
            ?>
            <div class="mess-date-divider"><?= $date_label ?></div>
            <?php } ?>
            
            <div class="mess-msg <?= $p['user_id'] == current_user_id() ? 'mine' : '' ?>" data-id="<?= $p['id'] ?>">
                <div class="mess-msg-avatar" title="<?= e($p['username']) ?>">
                    <?= strtoupper(substr($p['username'], 0, 2)) ?>
                </div>
                <div class="mess-msg-content">
                    <?php if($p['user_id'] != current_user_id()): ?>
                    <div class="mess-msg-name"><?= e($p['username']) ?></div>
                    <?php endif; ?>
                    <div class="mess-msg-bubble">
                        <?= nl2br(e($p['body'])) ?>
                        <div class="msg-actions">
                            <?php if($p['user_id'] == current_user_id() || $is_mod): ?>
                            <button class="msg-action-btn delete-msg" title="Delete" data-id="<?= $p['id'] ?>">🗑️</button>
                            <?php endif; ?>
                            <button class="msg-action-btn reply-msg" title="Reply">↩️</button>
                        </div>
                    </div>
                    <div class="mess-msg-info">
                        <?= date('g:i A', strtotime($p['created_at'])) ?>
                        <?php if($p['user_id'] == current_user_id()): ?>
                        <span class="msg-status seen">✓✓</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

<?php if($is_member): ?>
        <!-- Emoji Picker -->
        <div class="emoji-picker" id="emojiPicker">
            <div class="emoji-picker-header">
                <h5>Emojis</h5>
                <button type="button" class="emoji-picker-close" onclick="toggleEmojiPicker()">×</button>
            </div>
            <div class="emoji-categories">
                <button type="button" class="emoji-cat-btn active" data-cat="smile">😀</button>
                <button type="button" class="emoji-cat-btn" data-cat="animals">🐶</button>
                <button type="button" class="emoji-cat-btn" data-cat="food">🍕</button>
                <button type="button" class="emoji-cat-btn" data-cat="sports">⚽</button>
                <button type="button" class="emoji-cat-btn" data-cat="tech">💻</button>
                <button type="button" class="emoji-cat-btn" data-cat="hearts">❤️</button>
            </div>
            <div class="emoji-grid" id="emojiGrid">
                <!-- Emojis will be inserted by JS -->
            </div>
        </div>

        <!-- File Attachment Panel -->
        <div class="attachment-panel" id="attachmentPanel">
            <div class="attachment-options">
                <button type="button" class="attachment-option" onclick="document.getElementById('fileInput').click()">
                    <i>📷</i>
                    <span>Photo</span>
                </button>
                <button type="button" class="attachment-option" onclick="document.getElementById('fileInput').click()">
                    <i>📁</i>
                    <span>Document</span>
                </button>
                <button type="button" class="attachment-option" onclick="document.getElementById('fileInput').click()">
                    <i>📍</i>
                    <span>Location</span>
                </button>
            </div>
            <div class="attachment-preview" id="attachmentPreview">
                <img id="previewImage" src="" alt="Preview">
                <div class="attachment-preview-file" id="previewFile" style="display:none;">
                    <i>📄</i>
                    <div class="file-info">
                        <div class="file-name" id="fileName"></div>
                        <div class="file-size" id="fileSize"></div>
                    </div>
                    <button type="button" class="attachment-remove" onclick="removeAttachment()">×</button>
                </div>
            </div>
            <input type="file" id="fileInput" style="display:none;" accept="image/*,.pdf,.doc,.docx,.txt">
        </div>

        <form class="mess-input" method="POST" id="messageForm" enctype="multipart/form-data">
            <?= csrf_field() ?><input type="hidden" name="post_msg" value="1">
            <input type="file" id="fileInputHidden" name="attachment" style="display:none;">
            <button type="button" class="mess-input-btn" title="Emoji" onclick="toggleEmojiPicker()">😊</button>
            <button type="button" class="mess-input-btn" title="Attach File" onclick="toggleAttachmentPanel()">📎</button>
            <fieldset>
                <input type="text" name="body" placeholder="Type a message..." required autocomplete="off" id="messageInput">
            </fieldset>
            <button type="submit" class="mess-send-btn"><i class="bi bi-send"></i></button>
        </form>
        <?php else: ?>
        <form class="mess-input" method="POST" action="study-groups.php">
            <?= csrf_field() ?><input type="hidden" name="join" value="1"><input type="hidden" name="group_id" value="<?= $id ?>">
            <button type="submit" class="btn-gold" style="flex:1;height:44px;border-radius:22px;">Join Group to Chat</button>
        </form>
        <?php endif; ?>
    </div>

    <!-- MEMBERS PANEL -->
    <div class="mess-members">
        <div class="mess-members-header">
            <h4>Group Members</h4>
            <span style="color:#666;font-size:0.8rem;"><?= count($online_members) ?> online</span>
        </div>
        
        <div class="mess-members-search">
            <input type="text" placeholder="Search members..." id="memberSearch">
        </div>
        
        <div class="mess-members-list" id="membersList">
            <?php foreach($members as $m): ?>
            <div class="mess-member" data-name="<?= strtolower(e($m['name'])) ?>">
                <div class="mess-member-avatar">
                    <?= strtoupper(substr($m['username'], 0, 2)) ?>
                    <div class="mess-member-status <?= in_array($m['id'], $online_ids) ? 'online' : '' ?>"></div>
                </div>
                <div class="mess-member-info">
                    <div class="mess-member-name"><?= e($m['name']) ?></div>
                    <div class="mess-member-role">
                        <?php if($m['id'] == $group['owner_id']): ?>
                        <span class="mess-member-badge">Admin</span>
                        <?php elseif($m['group_role'] == 'moderator'): ?>
                        <span class="mess-member-badge mod">MOD</span>
                        <?php endif; ?>
                        ⭐ <?= $m['reputation'] ?>
                        <?php if(in_array($m['id'], $online_ids)): ?>
                        • <span style="color:#22c55e;">Active now</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Group Info -->
        <div class="mess-group-info">
            <div class="mess-group-info-title">About This Group</div>
            <div class="mess-group-stat">
                <span>📅</span> Created <?= date('M j, Y', strtotime($group['created_at'])) ?>
            </div>
            <div class="mess-group-stat">
                <span>👤</span> By <?= e($group['owner_name']) ?>
            </div>
            <?php if($group['description']): ?>
            <div class="mess-group-stat">
                <span>📝</span> <?= e($group['description']) ?>
            </div>
            <?php endif; ?>
            
            <?php if($is_member && !$is_owner): ?>
            <form method="POST">
                <?= csrf_field() ?><input type="hidden" name="leave" value="1">
                <button type="submit" class="leave-group-btn" onclick="return confirm('Leave this group?')">Leave Group</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// EMOJI DATA
const emojiCategories = {
    smile: ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊','😇','🥰','😍','🤩','😘','😗','☺️','😚','😙','🥲','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🥴','😵','🤯','🤠','🥳','🥸','😎','🤓','🧐'],
    animals: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🙈','🙉','🙊','🐒','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🐛','🦋','🐌','🐞','🐜','🦟','🦗','🕷️','🦂','🐢','🐍','🦎','🦖','🦕','🐙','🦑','🦐','🦞','🦀','🐡','🐠','🐟','🐬','🐳','🐋','🦈','🐊','🐅','🐆','🦓','🦍','🦧','🦣'],
    food: ['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🥬','🥒','🌶️','🫑','🌽','🥕','🫒','🧄','🧅','🥔','🍠','🥐','🥯','🍞','🥖','🥨','🧀','🥚','🍳','🧈','🥞','🧇','🥓','🥩','🍗','🍖','🦴','🌭','🍔','🍟','🍕','🫓','🥪','🥙','🧆','🌮','🌯','🫔','🥗','🥘','🫕','🍝','🍜','🍲','🍛','🍣','🍱','🥟','🦪','🍤','🍙','🍚','🍘','🍥','🥠','🥮','🍢','🍡','🍧','🍨','🍦','🥧','🧁','🍰','🎂','🍮','🍭','🍬','🍫','🍿','🍩','🍪','🌰','🥜','🍯','🥛','🍼','☕','🫖','🍵','🧃','🥤','🧋','🍶','🍺','🍻','🥂','🍷','🥃','🍸','🍹','🧉','🍾'],
    sports: ['⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🥏','🎱','🪀','🏓','🏸','🏒','🏑','🥍','🏏','🪃','🥅','⛳','🪁','🏹','🎣','🤿','🥊','🥋','🎽','🛹','🛼','🛷','⛸️','🥌','🎿','⛷️','🏂','🪂','🏋️','🤼','🤸','⛹️','🤺','🤾','🏌️','🏇','⛳','🚴','🚵','🏎️','🏁','🎪','🤹','🎭','🎨','🎬','🎤','🎧','🎼','🎹','🥁','🪘','🎷','🎺','🪗','🎸','🪕','🎻','🪈','🎲','♟️','🎯','🎳','🎮','🎰'],
    tech: ['💻','🖥️','🖨️','⌨️','🖱️','🖲️','💽','💾','💿','📀','📼','📷','📸','📹','🎥','📽️','🎞️','📞','☎️','📟','📠','📺','📻','🎙️','🎚️','🎛️','🧭','⏱️','⏲️','⏰','🕰️','⌛','⏳','📡','🔋','🔌','💡','🔦','🕯️','🪔','🧯','🛢️','💸','💵','💴','💶','💷','🪙','💰','💳','💎','⚖️','🪜','🧰','🪛','🔧','🔨','⚒️','🛠️','⛏️','🪚','🔩','⚙️','🪤','🧲','🔫','💣','🧨','🪓','🔪','🗡️','⚔️','🛡️','🚬','⚰️','🪦','⚱️','🏺','🔮','📿','🧿','💈','⚗️','🔭','🔬','🕳️','🩹','🩺','💊','💉','🩸','🧬','🦠','🧫','🧪'],
    hearts: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','☮️','✝️','☪️','🕉️','☸️','✡️','🔯','🕎','☯️','☦️','🛐','⛎','♈','♉','♊','♋','♌','♍','♎','♏','♐','♑','♒','♓','🆔','⚛️','🉑','☢️','☣️','📴','📳','🈶','🈚','🈸','🈺','🈷️','✴️','🆚','💮','🉐','㊙️','㊗️','🈴','🈵','🈹','🈲','🅰️','🅱️','🆎','🆑','🅾️','🆘','❌','⭕','🛑','⛔','📛','🚫','💯','💢','♨️','🚷','🚯','🚳','🚱','🔞','📵','🚭','❗','❕','❓','❔','‼️','⁉️','🔅','🔆','〽️','⚠️','🚸','🔱','⚜️','🔰','♻️','✅','🈯','💹','❇️','✳️','❎','🌐','💠','Ⓜ️','🌀','💤','🏧','🚾','♿','🅿️','🛗','🈳','🈂️','🛂','🛃','🛄','🛅','🚹','🚺','🚼','⚧','🚻','🚮','🎦','📶','🈁','🔣','ℹ️','🔤','🔡','🔠','🆖','🆗','🆙','🆒','🆕','🆓','0️⃣','1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟','🔢','#️⃣','*️⃣','⏏️','▶️','⏸️','⏯️','⏹️','⏺️','⏭️','⏮️','⏩','⏪','⏫','⏬','◀️','🔼','🔽','➡️','⬅️','⬆️','⬇️','↗️','↘️','↙️','↖️','↕️','↔️','↪️','↩️','⤴️','⤵️','🔀','🔁','🔂','🔄','🔃','🎵','🎶','➕','➖','➗','✖️','♾️','💲','💱','™️','©️','®️','〰️','➰','➿','🔚','🔙','🔛','🔝','🔜','✔️','☑️','🔘','🔴','🟠','🟡','🟢','🔵','🟣','⚫','⚪','🟤','🔺','🔻','🔸','🔹','🔶','🔷','🔳','🔲','▪️','▫️','◾','◽','◼️','◻️','🟥','🟧','🟨','🟩','🟦','🟪','⬛','⬜','🟫','🔈','🔇','🔉','🔊','🔔','🔕','📣','📢','👁‍🗨','💬','💭','🗯️','♠️','♣️','♥️','♦️','🃏','🎴','🀄','🕐','🕑','🕒','🕓','🕔','🕕','🕖','🕗','🕘','🕙','🕚','🕛','🕜','🕝','🕞','🕟','🕠','🕡','🕢','🕣','🕤','🕥','🕦','🕧']
};

// Load emojis for a category
function loadEmojis(category) {
    const grid = document.getElementById('emojiGrid');
    const emojis = emojiCategories[category] || emojiCategories.smile;
    grid.innerHTML = emojis.map(emoji => 
        `<button type="button" class="emoji-btn" onclick="addEmoji('${emoji}')">${emoji}</button>`
    ).join('');
    
    // Update active category
    document.querySelectorAll('.emoji-cat-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.cat === category);
    });
}

// Initialize first category
loadEmojis('smile');

// Category buttons
document.querySelectorAll('.emoji-cat-btn').forEach(btn => {
    btn.addEventListener('click', () => loadEmojis(btn.dataset.cat));
});

// Toggle emoji picker
function toggleEmojiPicker() {
    document.getElementById('emojiPicker').classList.toggle('active');
    document.getElementById('attachmentPanel').classList.remove('active');
}

// Add emoji to input
function addEmoji(emoji) {
    const input = document.getElementById('messageInput');
    input.value += emoji;
    input.focus();
}

// Toggle attachment panel
function toggleAttachmentPanel() {
    document.getElementById('attachmentPanel').classList.toggle('active');
    document.getElementById('emojiPicker').classList.remove('active');
}

// File input handling
const fileInput = document.getElementById('fileInput');
if (fileInput) {
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const preview = document.getElementById('attachmentPreview');
            const previewImage = document.getElementById('previewImage');
            const previewFile = document.getElementById('previewFile');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            
            preview.classList.add('active');
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                    previewFile.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                previewImage.style.display = 'none';
                previewFile.style.display = 'flex';
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
            }
            
            // Also copy to hidden form input
            document.getElementById('fileInputHidden').files = this.files;
        }
    });
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Remove attachment
function removeAttachment() {
    document.getElementById('attachmentPreview').classList.remove('active');
    document.getElementById('fileInput').value = '';
    document.getElementById('fileInputHidden').value = '';
}

// Close panels when clicking outside
document.addEventListener('click', function(e) {
    const emojiPicker = document.getElementById('emojiPicker');
    const attachmentPanel = document.getElementById('attachmentPanel');
    const emojiBtn = document.querySelector('[onclick="toggleEmojiPicker()"]');
    const attachBtn = document.querySelector('[onclick="toggleAttachmentPanel()"]');
    
    if (emojiPicker && !emojiPicker.contains(e.target) && !emojiBtn.contains(e.target)) {
        emojiPicker.classList.remove('active');
    }
    if (attachmentPanel && !attachmentPanel.contains(e.target) && !attachBtn.contains(e.target)) {
        attachmentPanel.classList.remove('active');
    }
});

// Auto-scroll to bottom
const chatBox = document.getElementById('chatBox');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

// Message input - submit on enter
const messageInput = document.getElementById('messageInput');
if (messageInput) {
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const form = document.getElementById('messageForm');
            if (this.value.trim()) {
                form.submit();
            }
        }
    });
}

// Member search
const memberSearch = document.getElementById('memberSearch');
if (memberSearch) {
    memberSearch.addEventListener('input', function() {
        const search = this.value.toLowerCase();
        const members = document.querySelectorAll('.mess-member');
        members.forEach(member => {
            const name = member.dataset.name;
            member.style.display = name.includes(search) ? 'flex' : 'none';
        });
    });
}

// Simulate typing indicator (for demo - would need WebSocket for real)
const msgInput = document.getElementById('messageInput');
if (msgInput) {
    let typingTimeout;
    msgInput.addEventListener('input', function() {
        // In real app, this would send typing status to server
    });
}

// Delete message confirmation
document.querySelectorAll('.delete-msg').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        if (confirm('Delete this message?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<?= csrf_field() ?><input type="hidden" name="delete_post" value="1"><input type="hidden" name="post_id" value="${this.dataset.id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    });
});

// Show message actions on hover
document.querySelectorAll('.mess-msg-bubble').forEach(bubble => {
    bubble.addEventListener('click', function(e) {
        if (e.target.classList.contains('msg-action-btn')) return;
        this.querySelector('.msg-actions').style.opacity = '1';
    });
});

// Close message actions when clicking elsewhere
document.addEventListener('click', function() {
    document.querySelectorAll('.msg-actions').forEach(actions => {
        actions.style.opacity = '0';
    });
});

// Auto-refresh every 30 seconds (for new messages)
setInterval(() => {
    // In production, use AJAX to fetch new messages
    // location.reload(); 
}, 30000);

console.log('PANTHERVERSE Messenger loaded successfully!');
</script>

<?php require_once 'includes/footer.php'; ?>


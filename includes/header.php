<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="csrf-token" content="<?= csrf_token() ?>">
<title><?= e($page_title ?? 'Home') ?> — PANTHERVERSE</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/night-owl.min.css">

<style>
/* ═══════════════════════════════════════════════════════════
   PANTHERVERSE — Purple/Gold Theme
   ═══════════════════════════════════════════════════════════ */
:root {
  --bg:        #0e0720;
  --bg2:       #160d2e;
  --bg3:       #1e1040;
  --surface:   #1a0e38;
  --surface2:  #231550;
  --border:    rgba(124,58,237,0.3);
  --border2:   rgba(244,166,35,0.25);
  --purple:    #7c3aed;
  --purple-l:  #9d5cf6;
  --purple-d:  #5b21b6;
  --gold:      #f4a623;
  --gold-l:    #fbbf24;
  --gold-d:    #d97706;
  --text:      #e8dff8;
  --text-m:    #a78bca;
  --text-d:    #6b4fa0;
  --green:     #10b981;
  --red:       #ef4444;
  --radius:    10px;
  --shadow:    0 4px 24px rgba(124,58,237,0.18);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
  font-family: 'Nunito', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  background-image:
    radial-gradient(ellipse 80% 40% at 50% -10%, rgba(124,58,237,0.15) 0%, transparent 60%),
    radial-gradient(ellipse 40% 30% at 90% 20%, rgba(244,166,35,0.06) 0%, transparent 50%);
}

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg); }
::-webkit-scrollbar-thumb { background: var(--purple-d); border-radius: 3px; }

/* ── Typography ── */
h1,h2,h3,h4 { font-family: 'Rajdhani', sans-serif; font-weight: 700; }
a { color: var(--gold); text-decoration: none; transition: color 0.15s; }
a:hover { color: var(--gold-l); }
code { font-size: 0.875em; background: var(--bg3); color: var(--gold); padding: 1px 5px; border-radius: 4px; }

/* ── NAVBAR ────────────────────────────────────────────────── */
.navbar {
  position: sticky; top: 0; z-index: 100;
  background: rgba(14,7,32,0.92);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
  padding: 0 20px;
  height: 60px;
  display: flex; align-items: center; gap: 16px;
}

.nav-logo {
  font-family: 'Rajdhani', sans-serif;
  font-size: 1.4rem; font-weight: 700;
  color: #fff; white-space: nowrap;
  display: flex; align-items: center; gap: 8px;
}
.nav-logo img { height: 32px; width: 32px; object-fit: contain; border-radius: 50%; }
.nav-logo .logo-fallback { font-size: 1.5rem; }
.nav-logo span { color: var(--gold); }

.nav-search {
  flex: 1; max-width: 340px;
  position: relative;
}
.nav-search input {
  width: 100%;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text);
  padding: 7px 12px 7px 34px;
  font-size: 0.875rem;
  font-family: 'Nunito', sans-serif;
  outline: none;
  transition: border-color 0.2s;
}
.nav-search input:focus { border-color: var(--purple); }
.nav-search input::placeholder { color: var(--text-d); }
.nav-search .si { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-d); font-size: 0.875rem; }

.nav-links { display: flex; gap: 4px; align-items: center; }
.nav-links a {
  color: var(--text-m); font-size: 0.85rem; font-weight: 600;
  padding: 5px 10px; border-radius: 7px; transition: all 0.15s;
}
.nav-links a:hover, .nav-links a.active { background: var(--surface2); color: var(--text); }

.nav-right { margin-left: auto; display: flex; align-items: center; gap: 10px; }

.btn-gold {
  background: linear-gradient(135deg, var(--gold), var(--gold-d));
  color: #1a0e38; font-weight: 700; font-size: 0.85rem;
  border: none; border-radius: 8px; padding: 7px 16px;
  cursor: pointer; transition: all 0.15s; text-decoration: none;
  display: inline-flex; align-items: center; gap: 6px;
}
.btn-gold:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(244,166,35,0.4); color: #1a0e38; }

.btn-purple {
  background: var(--purple);
  color: #fff; font-weight: 600; font-size: 0.875rem;
  border: none; border-radius: 8px; padding: 7px 16px;
  cursor: pointer; transition: all 0.15s; text-decoration: none;
  display: inline-flex; align-items: center; gap: 6px;
}
.btn-purple:hover { background: var(--purple-l); color: #fff; }

.btn-ghost {
  background: transparent; color: var(--text-m);
  border: 1px solid var(--border); border-radius: 8px;
  padding: 7px 16px; font-weight: 600; font-size: 0.85rem;
  cursor: pointer; transition: all 0.15s; text-decoration: none;
  display: inline-flex; align-items: center; gap: 6px;
}
.btn-ghost:hover { border-color: var(--purple-l); color: var(--text); }

.btn-sm { padding: 4px 12px !important; font-size: 0.8rem !important; }
.btn-danger { background: rgba(239,68,68,0.15); color: var(--red); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; padding: 5px 12px; cursor: pointer; font-size: 0.8rem; }
.btn-danger:hover { background: rgba(239,68,68,0.25); }

.notif-btn { position: relative; color: var(--text-m); font-size: 1.15rem; padding: 4px 6px; }
.notif-dot { position: absolute; top: 0; right: 0; background: var(--red); border-radius: 50%; width: 8px; height: 8px; }

.user-menu { position: relative; }
.user-btn {
  display: flex; align-items: center; gap: 8px;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 8px; padding: 5px 10px; cursor: pointer;
  font-size: 0.85rem; color: var(--text); font-family: 'Nunito', sans-serif;
}
.user-btn img { width: 26px; height: 26px; border-radius: 50%; object-fit: cover; }
.user-dropdown {
  display: none; position: absolute; right: 0; top: calc(100% + 8px);
  background: var(--surface2); border: 1px solid var(--border);
  border-radius: 10px; min-width: 180px; z-index: 200;
  box-shadow: 0 8px 32px rgba(0,0,0,0.4); padding: 6px;
}
.user-menu:hover .user-dropdown,
.user-menu:focus-within .user-dropdown { display: block; }
.user-dropdown a, .user-dropdown button {
  display: block; width: 100%; text-align: left;
  padding: 7px 12px; border-radius: 7px; font-size: 0.85rem;
  color: var(--text-m); background: none; border: none; cursor: pointer;
  font-family: 'Nunito', sans-serif; transition: all 0.12s;
}
.user-dropdown a:hover, .user-dropdown button:hover { background: var(--surface); color: var(--text); }
.user-dropdown hr { border: none; border-top: 1px solid var(--border); margin: 4px 0; }

/* ── HERO ──────────────────────────────────────────────────── */
.hero-banner {
  position: relative; overflow: hidden;
  background: linear-gradient(160deg, #1a0938 0%, #2d1060 40%, #1a0938 100%);
  border-bottom: 1px solid var(--border2);
  padding: 48px 24px 0;
}
.hero-glow {
  position: absolute; top: -60px; left: 50%; transform: translateX(-50%);
  width: 600px; height: 300px;
  background: radial-gradient(ellipse, rgba(124,58,237,0.3) 0%, transparent 70%);
  pointer-events: none;
}
.hero-content { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; gap: 32px; }
.hero-logo-ring {
  width: 100px; height: 100px; flex-shrink: 0;
  border-radius: 50%; border: 2px solid var(--gold);
  display: flex; align-items: center; justify-content: center;
  background: rgba(124,58,237,0.2);
  box-shadow: 0 0 32px rgba(244,166,35,0.3);
}
.hero-logo-ring img { width: 85px; height: 85px; border-radius: 50%; object-fit: cover; }
.hero-logo-fallback { font-size: 3rem; }
.hero-title {
  font-family: 'Rajdhani', sans-serif; font-size: 3.4rem; font-weight: 700;
  color: #fff; line-height: 1; letter-spacing: 1px;
}
.hero-title span { color: var(--gold); }
.hero-sub { color: var(--text-m); font-size: 1.1rem; margin: 8px 0 20px; max-width: 500px; }
.hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }
.stats-bar {
  max-width: 1100px; margin: 32px auto 0;
  display: flex; gap: 0;
  border-top: 1px solid var(--border2);
}
.stat-item {
  flex: 1; text-align: center; padding: 16px 8px;
  border-right: 1px solid var(--border2);
}
.stat-item:last-child { border-right: none; }
.stat-num { display: block; font-family: 'Rajdhani', sans-serif; font-size: 1.75rem; font-weight: 700; color: var(--gold); }
.stat-label { font-size: 0.78rem; color: var(--text-d); text-transform: uppercase; letter-spacing: 0.05em; }

/* ── ANNOUNCEMENTS ─────────────────────────────────────────── */
.announce-bar {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 20px; font-size: 0.875rem;
  border-left: 3px solid;
}
.announce-bar.priority-urgent  { background: rgba(239,68,68,0.08);   border-color: var(--red);    }
.announce-bar.priority-important{ background: rgba(244,166,35,0.08); border-color: var(--gold);   }
.announce-bar.priority-normal  { background: rgba(124,58,237,0.08);  border-color: var(--purple); }
.announce-meta { color: var(--text-d); font-size: 0.8rem; margin-left: 4px; }
.announce-link { margin-left: auto; color: var(--gold); font-size: 0.82rem; }

/* ── LAYOUT ────────────────────────────────────────────────── */
.section-wrap { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
.main-grid {
  max-width: 1100px; margin: 24px auto; padding: 0 20px;
  display: grid; grid-template-columns: 1fr 280px; gap: 20px;
}
@media(max-width:900px) { .main-grid { grid-template-columns: 1fr; } }

/* ── SECTION HEAD ──────────────────────────────────────────── */
.section-head {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 14px;
}
.section-head h2 { font-family: 'Rajdhani', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--text); }
.see-all { font-size: 0.82rem; color: var(--gold); }

/* ── QUESTION CARDS ────────────────────────────────────────── */
.q-list { display: flex; flex-direction: column; gap: 10px; }
.q-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 14px 16px;
  display: flex; gap: 14px; transition: border-color 0.15s, box-shadow 0.15s;
}
.q-card:hover { border-color: var(--purple-l); box-shadow: var(--shadow); }
.q-card.solved { border-left: 3px solid var(--green); }
.q-votes { display: flex; flex-direction: column; gap: 6px; min-width: 48px; }
.q-stat-box {
  background: var(--bg3); border: 1px solid var(--border);
  border-radius: 6px; padding: 4px 6px; text-align: center;
}
.q-stat-box .big { display: block; font-family: 'Rajdhani', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--purple-l); line-height: 1; }
.q-stat-box .lbl { font-size: 0.68rem; color: var(--text-d); }
.q-stat-box.green .big { color: var(--green); }
.q-body { flex: 1; min-width: 0; }
.q-title {
  font-family: 'Rajdhani', sans-serif; font-weight: 600; font-size: 1rem;
  color: var(--text); display: block; margin-bottom: 6px; line-height: 1.35;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.q-title:hover { color: var(--gold); }
.q-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 7px; }
.tag {
  background: rgba(124,58,237,0.15); color: var(--purple-l);
  border: 1px solid rgba(124,58,237,0.3); border-radius: 4px;
  font-size: 0.72rem; font-weight: 600; padding: 2px 7px;
  text-decoration: none; transition: all 0.12s;
}
.tag:hover { background: var(--purple); color: #fff; }
.q-meta { font-size: 0.78rem; color: var(--text-d); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.q-user { color: var(--purple-l); font-weight: 600; }
.q-user:hover { color: var(--gold); }
.rep { color: var(--gold); font-size: 0.75rem; }
.dot { color: var(--text-d); }

/* ── WIDGETS ───────────────────────────────────────────────── */
.widget { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 16px; overflow: hidden; }
.widget-head { padding: 10px 14px; font-family: 'Rajdhani', sans-serif; font-weight: 700; font-size: 0.95rem; background: var(--bg3); border-bottom: 1px solid var(--border); color: var(--text); }
.widget-body { padding: 10px 14px; }

.contrib-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.rank { font-family: 'Rajdhani', sans-serif; font-weight: 700; font-size: 1rem; color: var(--gold); width: 18px; text-align: center; }
.contrib-info { flex: 1; min-width: 0; }
.contrib-name { font-size: 0.85rem; font-weight: 600; color: var(--text-m); text-decoration: none; }
.contrib-name:hover { color: var(--gold); }
.contrib-rep { font-size: 0.78rem; color: var(--gold); white-space: nowrap; }
.role-tag { background: rgba(124,58,237,0.2); color: var(--purple-l); border-radius: 4px; font-size: 0.68rem; padding: 1px 5px; margin-left: 4px; }
.role-tag.admin { background: rgba(244,166,35,0.2); color: var(--gold); }

.nav-link-item {
  display: block; padding: 7px 10px; border-radius: 7px; font-size: 0.855rem;
  color: var(--text-m); text-decoration: none; transition: all 0.12s; margin-bottom: 2px;
}
.nav-link-item:hover { background: var(--bg3); color: var(--text); }
.admin-link { color: var(--gold) !important; }

/* ── FULL PAGE LAYOUT ──────────────────────────────────────── */
.page-wrap { max-width: 1100px; margin: 28px auto; padding: 0 20px; }
.page-grid { display: grid; grid-template-columns: 1fr 280px; gap: 20px; }
@media(max-width:900px){ .page-grid { grid-template-columns: 1fr; } }

/* ── CARDS ─────────────────────────────────────────────────── */
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
.card-head { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.card-title { font-family: 'Rajdhani', sans-serif; font-weight: 700; font-size: 1.1rem; color: var(--text); }
.card-body { padding: 18px; }

/* ── FORMS ─────────────────────────────────────────────────── */
.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--text-m); margin-bottom: 6px; }
.form-group input,
.form-group textarea,
.form-group select {
  width: 100%; background: var(--bg3); border: 1px solid var(--border);
  border-radius: 8px; color: var(--text); padding: 9px 12px;
  font-size: 0.9rem; font-family: 'Nunito', sans-serif; outline: none;
  transition: border-color 0.2s;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus { border-color: var(--purple); }
.form-group textarea { resize: vertical; min-height: 120px; }
.form-hint { font-size: 0.78rem; color: var(--text-d); margin-top: 4px; }
.form-error { font-size: 0.8rem; color: var(--red); margin-top: 4px; }

/* ── TABS ──────────────────────────────────────────────────── */
.tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border); margin-bottom: 18px; }
.tab-link { padding: 8px 14px; font-size: 0.85rem; font-weight: 600; color: var(--text-d); border-bottom: 2px solid transparent; margin-bottom: -1px; cursor: pointer; text-decoration: none; transition: all 0.15s; }
.tab-link:hover { color: var(--text); }
.tab-link.active { color: var(--gold); border-bottom-color: var(--gold); }

/* ── ALERTS ────────────────────────────────────────────────── */
.alert { padding: 10px 14px; border-radius: 8px; font-size: 0.875rem; margin-bottom: 16px; border-left: 3px solid; }
.alert-success { background: rgba(16,185,129,0.1); border-color: var(--green); color: #6ee7b7; }
.alert-error   { background: rgba(239,68,68,0.1);  border-color: var(--red);   color: #fca5a5; }
.alert-info    { background: rgba(124,58,237,0.1); border-color: var(--purple);color: var(--text-m); }
.alert-warn    { background: rgba(244,166,35,0.1); border-color: var(--gold);  color: var(--gold-l); }

/* ── VOTE BUTTONS ──────────────────────────────────────────── */
.vote-wrap { display: flex; flex-direction: column; align-items: center; gap: 6px; }
.vote-btn {
  width: 36px; height: 36px; border-radius: 50%;
  background: var(--bg3); border: 1px solid var(--border);
  color: var(--text-d); font-size: 1rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.15s;
}
.vote-btn:hover { border-color: var(--purple-l); color: var(--purple-l); }
.vote-btn.up.active   { background: rgba(124,58,237,0.2); border-color: var(--purple); color: var(--purple-l); }
.vote-btn.down.active { background: rgba(239,68,68,0.15); border-color: var(--red);    color: var(--red); }
.vote-count { font-family: 'Rajdhani', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--text); }

/* ── ANSWER BLOCKS ─────────────────────────────────────────── */
.answer-block { border: 1px solid var(--border); border-radius: var(--radius); padding: 18px; background: var(--surface); margin-bottom: 14px; }
.answer-block.accepted { border-color: var(--green); background: rgba(16,185,129,0.05); }
.accepted-badge { display: inline-flex; align-items: center; gap: 5px; background: rgba(16,185,129,0.15); color: var(--green); border-radius: 6px; padding: 3px 10px; font-size: 0.8rem; font-weight: 700; margin-bottom: 10px; }
.verified-badge { display: inline-flex; align-items: center; gap: 5px; background: rgba(124,58,237,0.15); color: var(--purple-l); border-radius: 6px; padding: 3px 10px; font-size: 0.78rem; font-weight: 700; }

/* ── USER CHIP ─────────────────────────────────────────────── */
.user-chip { display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; color: var(--text-d); }
.user-chip img { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; }
.user-chip a { color: var(--purple-l); font-weight: 600; }
.user-chip a:hover { color: var(--gold); }

/* ── PROSE (Q&A body) ──────────────────────────────────────── */
.prose { line-height: 1.75; font-size: 0.95rem; color: var(--text); }
.prose p  { margin-bottom: 14px; }
.prose pre { margin-bottom: 14px; border-radius: 8px; overflow: auto; }
.prose pre code { padding: 0; background: none; color: inherit; font-size: 0.875rem; }
.prose ul, .prose ol { padding-left: 20px; margin-bottom: 14px; }
.prose li  { margin-bottom: 4px; }
.prose strong { color: var(--text); }
.prose h2, .prose h3 { color: var(--gold); margin: 18px 0 8px; }
.prose blockquote { border-left: 3px solid var(--purple); padding-left: 14px; color: var(--text-m); }

/* ── PAGINATION ────────────────────────────────────────────── */
.pagination { display: flex; gap: 4px; justify-content: center; margin-top: 24px; }
.pagination a, .pagination span {
  padding: 6px 12px; border-radius: 7px; font-size: 0.85rem;
  background: var(--surface); border: 1px solid var(--border); color: var(--text-m);
}
.pagination a:hover { border-color: var(--purple); color: var(--text); }
.pagination .current { background: var(--purple); border-color: var(--purple); color: #fff; }

/* ── EMPTY STATE ───────────────────────────────────────────── */
.empty-state { text-align: center; padding: 48px 24px; }
.empty-icon { font-size: 3rem; margin-bottom: 12px; }
.empty-state p { color: var(--text-d); margin-bottom: 18px; }

/* ── BADGES ────────────────────────────────────────────────── */
.badge-pill { display: inline-flex; align-items: center; gap: 4px; font-size: 0.72rem; font-weight: 700; padding: 3px 8px; border-radius: 20px; }
.badge-gold   { background: rgba(244,166,35,0.15); color: var(--gold);     border: 1px solid rgba(244,166,35,0.3); }
.badge-purple { background: rgba(124,58,237,0.15); color: var(--purple-l); border: 1px solid rgba(124,58,237,0.3); }
.badge-green  { background: rgba(16,185,129,0.15); color: var(--green);    border: 1px solid rgba(16,185,129,0.3); }
.badge-red    { background: rgba(239,68,68,0.15);  color: var(--red);      border: 1px solid rgba(239,68,68,0.3); }

/* ── FOOTER ────────────────────────────────────────────────── */
.footer {
  background: var(--bg2); border-top: 1px solid var(--border);
  text-align: center; padding: 20px; margin-top: 48px;
  font-size: 0.8rem; color: var(--text-d);
}
.footer strong { color: var(--gold); font-family: 'Rajdhani', sans-serif; }

/* ── TABLE ─────────────────────────────────────────────────── */
.pv-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.pv-table th { background: var(--bg3); color: var(--text-m); padding: 10px 14px; text-align: left; font-weight: 700; border-bottom: 1px solid var(--border); }
.pv-table td { padding: 10px 14px; border-bottom: 1px solid rgba(124,58,237,0.1); color: var(--text); }
.pv-table tr:hover td { background: rgba(124,58,237,0.04); }

/* ── DIVIDER ───────────────────────────────────────────────── */
hr { border: none; border-top: 1px solid var(--border); margin: 20px 0; }

/* ── NAV DROPDOWNS ─────────────────────────────────────────── */
.nav-dropdown-wrap { position: relative; }
.nav-drop-trigger {
  color: var(--text-m); font-size: 0.85rem; font-weight: 600;
  padding: 5px 10px; border-radius: 7px; cursor: pointer;
  display: flex; align-items: center; gap: 4px; transition: all 0.15s;
  user-select: none;
}
.nav-drop-trigger:hover, .nav-drop-trigger.active { background: var(--surface2); color: var(--text); }
.nav-drop-menu {
  display: none; position: absolute; top: calc(100% + 8px); left: 0;
  background: var(--surface2); border: 1px solid var(--border);
  border-radius: 10px; min-width: 200px; z-index: 200;
  box-shadow: 0 8px 32px rgba(0,0,0,0.4); padding: 6px; white-space: nowrap;
}
.nav-dropdown-wrap:hover .nav-drop-menu,
.nav-dropdown-wrap:focus-within .nav-drop-menu { display: block; }
.nav-drop-menu a {
  display: block; padding: 8px 12px; border-radius: 7px;
  font-size: 0.85rem; color: var(--text-m); text-decoration: none; transition: all 0.12s;
}
.nav-drop-menu a:hover { background: var(--surface); color: var(--text); }

/* ── MOBILE APP DOCK ────────────────────────────────────────── */
.mobile-bottom-nav {
  display: none;
  position: fixed; bottom: 0; left: 0; width: 100%;
  background: rgba(14,7,32,0.92); backdrop-filter: blur(12px);
  border-top: 1px solid var(--border);
  z-index: 101; padding-bottom: env(safe-area-inset-bottom);
}
.mobile-nav-items { display: flex; justify-content: space-around; padding: 10px 0 6px; }
.mobile-nav-item {
  color: var(--text-d); font-size: 0.75rem; display: flex; flex-direction: column; align-items: center; gap: 4px; text-decoration: none;
}
.mobile-nav-item.active { color: var(--gold); }
.mobile-nav-item i { font-size: 1.25rem; }

/* ── RESPONSIVE NAV ────────────────────────────────────────── */
@media(max-width:768px) {
  .nav-links { display: none; }
  .mobile-bottom-nav { display: block; }
  body { padding-bottom: 70px; -webkit-tap-highlight-color: transparent; }
  .navbar { padding: 0 12px; }
  .nav-search { display: none; } /* Hide search bar from header on mobile to save space */
  .hero-content { flex-direction: column; text-align: center; gap: 16px; padding: 24px 16px 20px !important; }
  .hero-title { font-size: 2.2rem !important; } /* Force override if inline exists */
  .hero-sub { font-size: 0.95rem !important; margin: 8px auto 16px; text-align: center; }
  .hero-actions { justify-content: center; width: 100%; }
  .hero-actions a { padding: 10px 16px !important; font-size: 0.85rem !important; }
  .hero-logo-ring { width: 70px; height: 70px; margin: 0 auto; }
  .hero-logo-ring img { width: 60px; height: 60px; }
  .stats-bar { flex-wrap: wrap; border-top: 1px solid var(--border2); }
  .stat-item { flex: 1 1 40%; border-bottom: 1px solid var(--border2); border-right: none !important; padding: 12px 8px; }
  .stat-num { font-size: 1.3rem; }
  .user-btn span { display: none; } /* Hide username text next to profile picture */
  .user-btn i { display: none; }
  .btn-gold.btn-sm, .btn-ghost.btn-sm, .btn-purple.btn-sm { padding: 6px 10px !important; font-size: 0.8rem; }
}

/* ── PRELOADER ─────────────────────────────────────────────── */
#pv-preloader {
  position: fixed; inset: 0; z-index: 9999;
  /* Blend the provided background image with a dark purple/black gradient for an intense loading screen overlay */
  background: linear-gradient(to bottom, rgba(14, 7, 32, 0.8), rgba(26, 14, 56, 0.95)), url('/assets/hero_bg.png') center/cover no-repeat;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.6s;
}
#pv-preloader.loaded { opacity: 0; visibility: hidden; }

.preloader-orbit {
  position: relative; width: 140px; height: 140px;
  display: flex; align-items: center; justify-content: center;
}
.preloader-ring {
  position: absolute; inset: 0;
  border: 2px dashed rgba(244,166,35,0.4);
  border-radius: 50%;
  animation: orbit-spin 4s linear infinite;
}
.preloader-logo {
  width: 80px; height: 80px;
  border-radius: 50%;
  box-shadow: 0 0 30px rgba(124,58,237,0.5);
  animation: logo-pulse 2s ease-in-out infinite;
  z-index: 2;
  object-fit: contain; background: #1a0e38;
}
.preloader-text {
  margin-top: 32px;
  font-family: 'Rajdhani', sans-serif;
  font-weight: 700; font-size: 1.1rem;
  color: var(--gold); letter-spacing: 0.3em;
  text-transform: uppercase;
  text-shadow: 0 0 10px rgba(244,166,35,0.5);
}
.preloader-bar {
  width: 180px; height: 2px;
  background: rgba(124,58,237,0.2);
  margin-top: 12px; border-radius: 2px;
  overflow: hidden; position: relative;
}
.preloader-bar::after {
  content: ''; position: absolute; left: -100%; top: 0; height: 100%; width: 100%;
  background: linear-gradient(90deg, transparent, var(--gold), transparent);
  animation: bar-scan 1.5s infinite;
}

@keyframes orbit-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes logo-pulse { 0%, 100% { transform: scale(1); filter: brightness(1); } 50% { transform: scale(1.08); filter: brightness(1.3); } }
@keyframes bar-scan { 0% { left: -100%; } 100% { left: 100%; } }
</style>
<script>
// Prevent flash of content
if (!sessionStorage.getItem('pv_loaded')) {
    document.documentElement.style.overflow = 'hidden';
}
</script>
</head>
<body>

<!-- PRELOADER -->
<div id="pv-preloader">
  <div class="preloader-orbit">
    <div class="preloader-ring"></div>
    <img src="/assets/logo.png" class="preloader-logo" alt="logo" onerror="this.src='https://ui-avatars.com/api/?name=P&background=5B21B6&color=F4A623&bold=true'">
  </div>
  <div class="preloader-text">Initializing</div>
  <div class="preloader-bar"></div>
</div>

<script>
window.addEventListener('load', () => {
    const loader = document.getElementById('pv-preloader');
    if (sessionStorage.getItem('pv_loaded')) {
        loader.style.display = 'none';
        document.documentElement.style.overflow = '';
    } else {
        // Play the Panther Roar Audio Effect
        try {
            const roar = new Audio('/assets/panther3.mp3');
            roar.volume = 0.5;
            roar.play().catch(e => console.log('Autoplay blocked by browser policy:', e));
        } catch (e) {}

        setTimeout(() => {
            loader.classList.add('loaded');
            document.documentElement.style.overflow = '';
            sessionStorage.setItem('pv_loaded', 'true');
        }, 1200); // 1.2s minimum visual for first time
    }
});
</script>

<!-- NAVBAR -->
<?php
$_self = basename($_SERVER['PHP_SELF']);
$_path = $_SERVER['PHP_SELF'];
function nav_active(string $match): string {
    global $_self, $_path;
    return (strpos($_path, $match) !== false) ? 'active' : '';
}
// Unread DMs count
$_unread_msgs = 0;
if (is_logged_in()) {
    $bool_false = $GLOBALS['_sql_false'];
    $_unread_msgs = db_count("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=$bool_false", [current_user_id()]);
}
// Pending consultation requests (instructor)
$_pending_consults = 0;
if (is_logged_in() && in_array(current_user_role(),['instructor','admin'])) {
    $_pending_consults = db_count("SELECT COUNT(*) FROM consultations WHERE instructor_id=? AND status='pending'", [current_user_id()]);
}
?>
<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <img src="/assets/logo.png" alt="logo" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
    <span class="logo-fallback" style="display:none">🐆</span>
    PANTHER<span>VERSE</span>
  </a>

  <!-- Search → goes to advanced search -->
  <form class="nav-search" action="search.php" method="GET">
    <i class="bi bi-search si"></i>
    <input type="text" name="q" placeholder="Search anything..." value="<?= e($_GET['q'] ?? '') ?>">
  </form>

  <!-- Primary nav links -->
  <div class="nav-links">
    <?php
    $base = '/';
    ?>
    <!-- Explore dropdown -->
    <div class="nav-dropdown-wrap" tabindex="0">
      <span class="nav-drop-trigger <?= nav_active('question') || nav_active('forum') || nav_active('resource') || nav_active('showcase') ? 'active' : '' ?>">
        Explore <i class="bi bi-chevron-down" style="font-size:0.6rem;"></i>
      </span>
      <div class="nav-drop-menu">
        <a href="<?= $base ?>questions.php"><i class="bi bi-question-circle"></i> Q&amp;A</a>
        <a href="<?= $base ?>questions.php?tab=most_liked"><i class="bi bi-heart"></i> Most Liked Questions</a>
        <a href="<?= $base ?>forums.php"><i class="bi bi-chat-square-text"></i> Forums</a>
        <a href="<?= $base ?>resources.php"><i class="bi bi-folder2"></i> Resources</a>
        <a href="<?= $base ?>showcase.php"><i class="bi bi-lightning"></i> Showcase</a>
        <a href="<?= $base ?>search.php"><i class="bi bi-search"></i> Advanced Search</a>
      </div>
    </div>

    <!-- Community dropdown -->
    <div class="nav-dropdown-wrap" tabindex="0">
      <span class="nav-drop-trigger <?= nav_active('study-group') || nav_active('leaderboard') || nav_active('consult') ? 'active' : '' ?>">
        Community <i class="bi bi-chevron-down" style="font-size:0.6rem;"></i>
      </span>
      <div class="nav-drop-menu">
        <a href="<?= $base ?>study-groups.php"><i class="bi bi-book"></i> Study Groups</a>
        <a href="<?= $base ?>consultations.php"><i class="bi bi-calendar3"></i> Consultations</a>
        <a href="<?= $base ?>leaderboard.php"><i class="bi bi-trophy"></i> Leaderboard</a>
        <a href="<?= $base ?>directory.php"><i class="bi bi-people"></i> User Directory</a>
        <a href="<?= $base ?>announcements.php"><i class="bi bi-megaphone"></i> Announcements</a>
        <a href="<?= $base ?>suggestions.php"><i class="bi bi-lightbulb"></i> Upgrades</a>
      </div>
    </div>

    <a href="<?= $base ?>calendar.php" class="<?= nav_active('calendar') ?>"><i class="bi bi-calendar3"></i> Calendar</a>
  </div>

  <div class="nav-right">
    <?php if (is_logged_in()):
      $cu = current_user(); ?>

      <a href="<?= $base ?>ask.php" class="btn-gold btn-sm"><i class="bi bi-plus-lg"></i> Ask</a>

      <!-- Messages icon -->
      <a href="<?= $base ?>messages.php" class="notif-btn" title="Messages" style="position:relative;">
        <i class="bi bi-chat-dots"></i>
        <?php if($_unread_msgs > 0): ?><span class="notif-dot"></span><?php endif; ?>
      </a>

      <!-- Notifications icon -->
      <a href="<?= $base ?>notifications.php" class="notif-btn" title="Notifications">
        <i class="bi bi-bell"></i>
        <?php $unread = unread_notifications(); if($unread > 0): ?>
          <span class="notif-dot"></span>
        <?php endif; ?>
      </a>

      <!-- Pending consultation badge for instructors -->
      <?php if($_pending_consults > 0): ?>
      <a href="<?= $base ?>my-consultations.php" title="<?= $_pending_consults ?> pending consultation request<?= $_pending_consults>1?'s':'' ?>" style="position:relative;color:var(--gold);font-size:1.1rem;padding:4px 6px;">
        <i class="bi bi-calendar-check"></i>
        <span style="position:absolute;top:0;right:0;background:var(--red);color:#fff;border-radius:50%;width:15px;height:15px;font-size:0.6rem;font-weight:700;display:flex;align-items:center;justify-content:center;"><?= $_pending_consults ?></span>
      </a>
      <?php endif; ?>

      <div class="user-menu" tabindex="0">
        <div class="user-btn">
          <img src="<?= avatar_url($cu['username']) ?>" alt="avatar">
          <span><?= e($cu['username']) ?></span>
          <i class="bi bi-chevron-down" style="font-size:0.7rem;color:var(--text-d);"></i>
        </div>
        <div class="user-dropdown">
          <a href="<?= $base ?>dashboard.php"><i class="bi bi-bar-chart-line"></i> My Dashboard</a>
          <a href="<?= $base ?>profile.php?u=<?= urlencode($cu['username']) ?>"><i class="bi bi-person"></i> Profile</a>
          <a href="<?= $base ?>my-questions.php"><i class="bi bi-question-circle"></i> My Questions</a>
          <a href="<?= $base ?>my-consultations.php"><i class="bi bi-calendar3"></i> My Consultations</a>
          <a href="<?= $base ?>messages.php"><i class="bi bi-chat-dots"></i> Messages <?= $_unread_msgs>0?"<span style='background:var(--red);color:#fff;border-radius:10px;padding:0 5px;font-size:0.7rem;'>{$_unread_msgs}</span>":'' ?></a>
          <a href="<?= $base ?>bookmarks.php"><i class="bi bi-bookmark"></i> Bookmarks</a>
          <a href="<?= $base ?>settings.php"><i class="bi bi-gear"></i> Settings</a>
          <?php if(in_array(current_user_role(),['instructor','admin'])): ?>
          <hr>
          <a href="<?= $base ?>my-consultations.php" style="color:var(--gold);"><i class="bi bi-mortarboard"></i> Manage Consultations <?= $_pending_consults>0?"({$_pending_consults})":'' ?></a>
          <?php endif; ?>
          <?php if(current_user_role()==='admin'): ?>
          <a href="<?= $base ?>admin/" style="color:var(--gold);"><i class="bi bi-shield-check"></i> Admin Panel</a>
          <?php endif; ?>
          <hr>
          <form method="POST" action="<?= $base ?>logout.php">
            <?= csrf_field() ?>
            <button type="submit" style="color:var(--red);"><i class="bi bi-box-arrow-right"></i> Logout</button>
          </form>
        </div>
      </div>

    <?php else: ?>
      <a href="<?= $base ?>login.php"    class="btn-ghost btn-sm">Login</a>
      <a href="<?= $base ?>register.php" class="btn-gold btn-sm">Join</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Mobile Bottom Dock Navigation -->
<div class="mobile-bottom-nav">
  <div class="mobile-nav-items">
    <a href="<?= $base ?>index.php" class="mobile-nav-item <?= nav_active('index.php') ? 'active' : '' ?>"><i class="bi bi-house"></i> Home</a>
    <a href="<?= $base ?>questions.php" class="mobile-nav-item <?= nav_active('question') ? 'active' : '' ?>"><i class="bi bi-compass"></i> Q&amp;A</a>
    <a href="<?= $base ?>forums.php" class="mobile-nav-item <?= nav_active('forum') ? 'active' : '' ?>"><i class="bi bi-chat-square-text"></i> Forums</a>
    <a href="#" class="mobile-nav-item" onclick="document.getElementById('mobileOverlay').style.display='flex';return false;"><i class="bi bi-grid-fill"></i> Menu</a>
  </div>
</div>

<!-- Immersive Mobile Menu Overlay -->
<div id="mobileOverlay" style="display:none;position:fixed;inset:0;background:rgba(14,7,32,0.96);backdrop-filter:blur(16px);z-index:9999;flex-direction:column;padding:30px 24px;overflow-y:auto;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;">
    <h2 style="font-family:'Rajdhani',sans-serif;font-size:1.8rem;color:var(--gold);margin:0;font-weight:700;">Navigation</h2>
    <i class="bi bi-x-circle" style="font-size:1.8rem;color:#fff;cursor:pointer;opacity:0.8;" onclick="document.getElementById('mobileOverlay').style.display='none'"></i>
  </div>
  
  <h3 style="color:var(--text-d);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.1em;margin:0 0 10px;">Explore</h3>
  <a href="<?= $base ?>search.php" style="color:#fff;padding:14px 0;border-bottom:1px solid var(--border);font-size:1.15rem;font-weight:600;text-decoration:none;display:flex;align-items:center;"><i class="bi bi-search" style="margin-right:12px;color:var(--purple-l);font-size:1.3rem;"></i> Search</a>
  <a href="<?= $base ?>resources.php" style="color:#fff;padding:14px 0;border-bottom:1px solid var(--border);font-size:1.15rem;font-weight:600;text-decoration:none;display:flex;align-items:center;"><i class="bi bi-folder2" style="margin-right:12px;color:var(--purple-l);font-size:1.3rem;"></i> Resources</a>
  <a href="<?= $base ?>showcase.php" style="color:#fff;padding:14px 0;border-bottom:1px solid var(--border);font-size:1.15rem;font-weight:600;text-decoration:none;display:flex;align-items:center;"><i class="bi bi-lightning" style="margin-right:12px;color:var(--purple-l);font-size:1.3rem;"></i> Showcase</a>

  <h3 style="color:var(--text-d);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.1em;margin:24px 0 10px;">Community</h3>
  <a href="<?= $base ?>study-groups.php" style="color:#fff;padding:14px 0;border-bottom:1px solid var(--border);font-size:1.15rem;font-weight:600;text-decoration:none;display:flex;align-items:center;"><i class="bi bi-book" style="margin-right:12px;color:var(--gold);font-size:1.3rem;"></i> Study Groups</a>
  <a href="<?= $base ?>consultations.php" style="color:#fff;padding:14px 0;border-bottom:1px solid var(--border);font-size:1.15rem;font-weight:600;text-decoration:none;display:flex;align-items:center;"><i class="bi bi-calendar3" style="margin-right:12px;color:var(--gold);font-size:1.3rem;"></i> Consultations</a>
  <a href="<?= $base ?>leaderboard.php" style="color:#fff;padding:14px 0;border-bottom:1px solid var(--border);font-size:1.15rem;font-weight:600;text-decoration:none;display:flex;align-items:center;"><i class="bi bi-trophy" style="margin-right:12px;color:var(--gold);font-size:1.3rem;"></i> Leaderboard</a>
  <a href="<?= $base ?>directory.php" style="color:#fff;padding:14px 0;border-bottom:1px solid var(--border);font-size:1.15rem;font-weight:600;text-decoration:none;display:flex;align-items:center;"><i class="bi bi-people" style="margin-right:12px;color:var(--gold);font-size:1.3rem;"></i> User Directory</a>
  <a href="<?= $base ?>announcements.php" style="color:#fff;padding:14px 0;border-bottom:1px solid var(--border);font-size:1.15rem;font-weight:600;text-decoration:none;display:flex;align-items:center;"><i class="bi bi-megaphone" style="margin-right:12px;color:var(--gold);font-size:1.3rem;"></i> Announcements</a>
  <a href="<?= $base ?>suggestions.php" style="color:#fff;padding:14px 0;border-bottom:1px solid var(--border);font-size:1.15rem;font-weight:600;text-decoration:none;display:flex;align-items:center;"><i class="bi bi-lightbulb" style="margin-right:12px;color:var(--gold);font-size:1.3rem;"></i> Upgrades</a>

  <h3 style="color:var(--text-d);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.1em;margin:24px 0 10px;">More</h3>
  <a href="<?= $base ?>calendar.php" style="color:#fff;padding:14px 0;border-bottom:1px solid var(--border);font-size:1.15rem;font-weight:600;text-decoration:none;display:flex;align-items:center;margin-bottom:30px;"><i class="bi bi-calendar-event" style="margin-right:12px;color:#10b981;font-size:1.3rem;"></i> Calendar</a>
</div>
<?php
// Show flash messages globally
$flash_ok  = flash('success');
$flash_err = flash('error');
if ($flash_ok || $flash_err): ?>
<div style="max-width:1100px;margin:12px auto;padding:0 20px;">
  <?php if($flash_ok): ?><div class="alert alert-success"><?= e($flash_ok) ?></div><?php endif; ?>
  <?php if($flash_err): ?><div class="alert alert-error"><?= e($flash_err) ?></div><?php endif; ?>
</div>
<?php endif; ?>

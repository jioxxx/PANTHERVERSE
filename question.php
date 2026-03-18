<?php
require_once 'includes/session.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$q  = db_row("SELECT q.*,u.username,u.reputation,u.role as user_role,u.id as author_id
              FROM questions q JOIN users u ON q.user_id=u.id
              WHERE q.id=? AND q.deleted_at IS NULL", [$id]);
if (!$q) { header('HTTP/1.0 404 Not Found'); die('<div style="padding:40px;text-align:center;color:#f4a623;font-family:monospace;background:#0e0720;">Question not found.</div>'); }

// Increment view count
db_exec("UPDATE questions SET view_count=view_count+1 WHERE id=?", [$id]);

// Tags
$tags = db_rows("SELECT t.name FROM tags t JOIN question_tag qt ON t.id=qt.tag_id WHERE qt.question_id=?", [$id]);

// User vote on question
$user_vote_q = 0;
$user_liked_q = false;
$user_bookmarked_q = false;
if (is_logged_in()) {
    $v = db_row("SELECT value FROM votes WHERE user_id=? AND voteable_id=? AND voteable_type='App\\\\Models\\\\Question'", [current_user_id(), $id]);
    $user_vote_q = $v ? (int)$v['value'] : 0;
    $user_liked_q = is_liked(current_user_id(), $id, 'question');
    $user_bookmarked_q = is_bookmarked(current_user_id(), $id, 'question');
}

// Answers
$answers = db_rows("
    SELECT a.*,u.username,u.reputation,u.id as author_id
    FROM answers a JOIN users u ON a.user_id=u.id
    WHERE a.question_id=? AND a.deleted_at IS NULL
    ORDER BY a.is_accepted DESC, a.vote_count DESC
", [$id]);

// Answer votes
$answer_votes = [];
if (is_logged_in()) {
    $avs = db_rows("SELECT voteable_id, value FROM votes WHERE user_id=? AND voteable_type='App\\\\Models\\\\Answer'", [current_user_id()]);
    foreach($avs as $av) $answer_votes[$av['voteable_id']] = (int)$av['value'];
}

// Comments on question
$q_comments = db_rows("SELECT c.*,u.username FROM comments c JOIN users u ON c.user_id=u.id WHERE c.commentable_id=? AND c.commentable_type='App\\\\Models\\\\Question' AND c.deleted_at IS NULL ORDER BY c.created_at", [$id]);

// Post answer
$ans_error = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['post_answer'])) {
    require_login();
    csrf_check();
    $body = trim($_POST['body'] ?? '');
    if (strlen($body) < 20) $ans_error = 'Answer must be at least 20 characters.';
    else {
        $bool_false = $GLOBALS['_sql_false'];
        $aid = db_insert("INSERT INTO answers (question_id,user_id,body,is_accepted,is_instructor_verified,vote_count,created_at,updated_at) VALUES (?,?,?,$bool_false,$bool_false,0,NOW(),NOW())",
            [$id, current_user_id(), strip_tags($body,'<p><br><strong><em><ul><ol><li><code><pre><h2><h3><blockquote><a>')]);
        if ($q['author_id'] !== current_user_id())
            send_notification($q['author_id'],'new_answer',['question_title'=>$q['title'],'question_id'=>$id,'answerer'=>current_user()['username']]);
        if (current_user_id() !== $q['author_id'])
            add_reputation(current_user_id(), 3, 'Posted an answer');
        flash('success','Your answer has been posted!');
        redirect("question.php?id=$id#answer-$aid");
    }
}

// Post comment
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['post_comment'])) {
    require_login(); csrf_check();
    $cbody = trim($_POST['comment_body'] ?? '');
    if (strlen($cbody) >= 10) {
        db_insert("INSERT INTO comments (user_id,commentable_id,commentable_type,body,created_at,updated_at) VALUES (?,?,'App\\\\Models\\\\Question',?,NOW(),NOW())",
            [current_user_id(), $id, strip_tags($cbody)]);
        redirect("question.php?id=$id");
    }
}

// Accept answer
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['accept_answer'])) {
    require_login(); csrf_check();
    $aid = (int)$_POST['answer_id'];
    if (current_user_id() == $q['author_id']) {
        $bool_true = $GLOBALS['_sql_true'];
        $bool_false = $GLOBALS['_sql_false'];
        db_exec("UPDATE answers SET is_accepted=$bool_false WHERE question_id=?", [$id]);
        db_exec("UPDATE answers SET is_accepted=$bool_true WHERE id=? AND question_id=?", [$aid,$id]);
        db_exec("UPDATE questions SET is_solved=$bool_true,status='answered',accepted_answer_id=? WHERE id=?", [$aid,$id]);
        $ans_author = db_row("SELECT user_id FROM answers WHERE id=?", [$aid]);
        if ($ans_author && $ans_author['user_id'] != current_user_id()) {
            add_reputation($ans_author['user_id'], 25, 'Answer accepted');
            send_notification($ans_author['user_id'],'answer_accepted',['question_title'=>$q['title'],'question_id'=>$id]);
        }
        flash('success','Answer accepted!');
        redirect("question.php?id=$id");
    }
}

$page_title = e($q['title']);
require_once 'includes/header.php';
?>

<div class="page-wrap">
  <div style="margin-bottom:16px;">
    <nav style="font-size:0.8rem;color:var(--text-d);margin-bottom:10px;">
      <a href="questions.php" style="color:var(--text-d);">Questions</a> › Question
    </nav>
    <h1 style="font-family:'Rajdhani',sans-serif;font-size:1.4rem;font-weight:700;line-height:1.35;">
      <?= $q['is_solved'] ? '✅ ' : '' ?><?= e($q['title']) ?>
    </h1>
    <div style="display:flex;gap:16px;font-size:0.78rem;color:var(--text-d);margin-top:8px;flex-wrap:wrap;">
      <span><i class="bi bi-clock"></i> Asked <?= time_ago($q['created_at']) ?></span>
      <span><i class="bi bi-eye"></i> <?= number_format($q['view_count']) ?> views</span>
      <span><i class="bi bi-star"></i> <?= $q['vote_count'] ?> votes</span>
      <span><i class="bi bi-chat"></i> <?= count($answers) ?> answers</span>
    </div>
  </div>
  <hr>

  <!-- Question Body -->
  <div style="display:flex;gap:20px;margin-top:20px;">
    <!-- Votes -->
    <div class="vote-wrap" data-voteable="question-<?= $id ?>">
      <button class="vote-btn up <?= $user_vote_q===1?'active':'' ?>" title="Upvote">▲</button>
      <span class="vote-count"><?= $q['vote_count'] ?></span>
      <button class="vote-btn down <?= $user_vote_q===-1?'active':'' ?>" title="Downvote">▼</button>
    </div>
    <!-- Content -->
    <div style="flex:1;min-width:0;">
      <div class="prose"><?= $q['body'] ?></div>

      <!-- Tags -->
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin:16px 0;">
        <?php foreach($tags as $t): ?>
        <a href="questions.php?tag=<?= urlencode($t['name']) ?>" class="tag"><?= e($t['name']) ?></a>
        <?php endforeach; ?>
      </div>

      <!-- Like & Bookmark Actions -->
      <div style="display:flex;align-items:center;gap:12px;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);flex-wrap:wrap;">
        <?php if(is_logged_in()): ?>
        <form method="POST" action="api/like.php" class="like-form" data-type="question" data-id="<?= $id ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="question">
          <input type="hidden" name="id" value="<?= $id ?>">
          <button type="submit" class="btn-<?= $user_liked_q ? 'gold' : 'ghost' ?> btn-sm" id="like-btn-<?= $id ?>">
            <i class="bi bi-heart<?= $user_liked_q ? '-fill' : '' ?>"></i> 
            <?= $user_liked_q ? 'Liked' : 'Like' ?>
          </button>
        </form>
        <span style="font-size:0.85rem;color:var(--text-d);" id="like-count-<?= $id ?>">
          <?= $q['like_count'] ?? 0 ?> likes
        </span>
        <form method="POST" action="api/bookmark.php" class="bookmark-form" data-type="question" data-id="<?= $id ?>" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="question">
          <input type="hidden" name="id" value="<?= $id ?>">
          <button type="submit" class="btn-ghost btn-sm bookmark-btn<?= $user_bookmarked_q ? ' bookmarked' : '' ?>" title="<?= $user_bookmarked_q ? 'Remove bookmark' : 'Bookmark this question' ?>" style="<?= $user_bookmarked_q ? 'color:var(--gold);border-color:var(--gold);' : '' ?>">
            <i class="bi bi-bookmark<?= $user_bookmarked_q ? '-fill' : '' ?>"></i> <span class="bm-label"><?= $user_bookmarked_q ? 'Saved' : 'Save' ?></span>
          </button>
        </form>
        <?php else: ?>
        <a href="login.php" class="btn-ghost btn-sm"><i class="bi bi-heart"></i> Like</a>
        <a href="login.php" class="btn-ghost btn-sm"><i class="bi bi-bookmark"></i> Bookmark</a>
        <?php endif; ?>
      </div>

      <!-- Author + Actions -->
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-top:16px;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <?php if(is_logged_in() && (current_user_id()==$q['author_id'] || current_user_role()==='admin')): ?>
          <a href="edit-question.php?id=<?= $id ?>" class="btn-ghost btn-sm"><i class="bi bi-pencil"></i> Edit</a>
          <form method="POST" action="delete-question.php" style="display:inline;" onsubmit="return confirm('Delete this question?')">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn-danger"><i class="bi bi-trash"></i> Delete</button>
          </form>
          <?php endif; ?>
        </div>
        <div class="card" style="padding:12px;min-width:180px;">
          <div style="font-size:0.72rem;color:var(--text-d);margin-bottom:8px;">asked <?= date('M j, Y', strtotime($q['created_at'])) ?></div>
          <div style="display:flex;align-items:center;gap:8px;">
            <img src="<?= avatar_url($q['username']) ?>" style="width:36px;height:36px;border-radius:50%;" alt="">
            <div>
              <a href="profile.php?u=<?= urlencode($q['username']) ?>" style="font-weight:700;font-size:0.875rem;color:var(--purple-l);">@<?= e($q['username']) ?></a>
              <div style="font-size:0.75rem;color:var(--gold);"><i class="bi bi-star-fill" style="font-size:0.75rem;color:var(--gold);"></i> <?= number_format($q['reputation']) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Q Comments -->
      <?php if($q_comments): ?>
      <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border);">
        <?php foreach($q_comments as $c): ?>
        <div style="font-size:0.83rem;color:var(--text-m);margin-bottom:6px;display:flex;gap:6px;">
          <span style="color:var(--text-d);">–</span>
          <span><?= e($c['body']) ?>
            <span style="color:var(--text-d);font-size:0.78rem;margin-left:6px;">
              <a href="profile.php?u=<?= urlencode($c['username']) ?>" style="color:var(--purple-l);">@<?= e($c['username']) ?></a>
              · <?= time_ago($c['created_at']) ?>
            </span>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if(is_logged_in()): ?>
      <form method="POST" style="margin-top:10px;display:flex;gap:8px;">
        <?= csrf_field() ?><input type="hidden" name="post_comment" value="1">
        <input type="text" name="comment_body" placeholder="Add a comment..." style="flex:1;background:rgba(124,58,237,0.08);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:6px 10px;font-size:0.82rem;font-family:'Nunito',sans-serif;outline:none;">
        <button type="submit" class="btn-ghost btn-sm">Comment</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── ANSWERS ── -->
  <h2 style="font-family:'Rajdhani',sans-serif;font-size:1.2rem;font-weight:700;margin:32px 0 14px;">
    <i class="bi bi-chat-left-text"></i> <?= count($answers) ?> <?= count($answers)===1?'Answer':'Answers' ?>
  </h2>

  <?php foreach($answers as $a): ?>
  <div id="answer-<?= $a['id'] ?>" class="answer-block <?= $a['is_accepted']?'accepted':'' ?>">
    <?php if($a['is_accepted']): ?>
    <div class="accepted-badge"><i class="bi bi-check-circle-fill"></i> Accepted Answer
      <?php if($a['is_instructor_verified']): ?> <span class="verified-badge" style="margin-left:6px;"><i class="bi bi-mortarboard-fill"></i> Instructor Verified</span><?php endif; ?>
    </div>
    <?php elseif($a['is_instructor_verified']): ?>
    <div style="margin-bottom:10px;"><span class="verified-badge"><i class="bi bi-mortarboard-fill"></i> Instructor Verified</span></div>
    <?php endif; ?>

    <div style="display:flex;gap:16px;">
      <div class="vote-wrap" data-voteable="answer-<?= $a['id'] ?>">
        <button class="vote-btn up <?= ($answer_votes[$a['id']]??0)===1?'active':'' ?>">▲</button>
        <span class="vote-count"><?= $a['vote_count'] ?></span>
        <button class="vote-btn down <?= ($answer_votes[$a['id']]??0)===-1?'active':'' ?>">▼</button>
        <?php if(is_logged_in() && current_user_id()==$q['author_id'] && !$a['is_accepted']): ?>
        <form method="POST" style="margin-top:4px;">
          <?= csrf_field() ?>
          <input type="hidden" name="accept_answer" value="1">
          <input type="hidden" name="answer_id" value="<?= $a['id'] ?>">
          <button type="submit" class="vote-btn" title="Accept this answer" style="color:var(--green);border-color:var(--green);">✓</button>
        </form>
        <?php endif; ?>
        <?php if(is_logged_in() && is_staff()): ?>
        <form method="POST" action="verify-answer.php">
          <?= csrf_field() ?>
          <input type="hidden" name="answer_id" value="<?= $a['id'] ?>">
          <input type="hidden" name="question_id" value="<?= $id ?>">
          <button type="submit" class="vote-btn" title="<?= $a['is_instructor_verified']?'Remove verification':'Verify answer' ?>" style="<?= $a['is_instructor_verified']?'color:var(--purple-l);border-color:var(--purple)':'' ?>"><i class="bi bi-mortarboard"></i></button>
        </form>
        <?php endif; ?>
      </div>

      <div style="flex:1;min-width:0;">
        <div class="prose"><?= $a['body'] ?></div>
        
        <!-- Answer Like Button -->
        <?php 
        $answer_liked = false;
        if (is_logged_in()) {
            $answer_liked = is_liked(current_user_id(), $a['id'], 'answer');
        }
        ?>
        <div style="display:flex;align-items:center;gap:12px;margin-top:10px;padding-top:10px;border-top:1px solid var(--border);">
          <?php if(is_logged_in()): ?>
          <form method="POST" action="api/like.php" class="like-form" data-type="answer" data-id="<?= $a['id'] ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="answer">
            <input type="hidden" name="id" value="<?= $a['id'] ?>">
            <button type="submit" class="btn-<?= $answer_liked ? 'gold' : 'ghost' ?> btn-sm" id="like-btn-<?= $a['id'] ?>">
              <i class="bi bi-heart<?= $answer_liked ? '-fill' : '' ?>"></i> 
              <?= $answer_liked ? 'Liked' : 'Like' ?>
            </button>
          </form>
          <?php else: ?>
          <a href="login.php" class="btn-ghost btn-sm"><i class="bi bi-heart"></i> Like</a>
          <?php endif; ?>
          <span style="font-size:0.85rem;color:var(--text-d);" id="like-count-<?= $a['id'] ?>">
            <?= $a['like_count'] ?? 0 ?> likes
          </span>
        </div>
        
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-top:10px;">
          <div style="display:flex;gap:6px;">
            <?php if(is_logged_in() && (current_user_id()==$a['author_id'] || current_user_role()==='admin')): ?>
            <form method="POST" action="delete-answer.php" onsubmit="return confirm('Delete this answer?')">
              <?= csrf_field() ?>
              <input type="hidden" name="answer_id" value="<?= $a['id'] ?>">
              <input type="hidden" name="question_id" value="<?= $id ?>">
              <button type="submit" class="btn-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
            <?php endif; ?>
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <img src="<?= avatar_url($a['username']) ?>" style="width:28px;height:28px;border-radius:50%;" alt="">
            <div>
              <a href="profile.php?u=<?= urlencode($a['username']) ?>" style="font-weight:700;font-size:0.83rem;color:var(--purple-l);">@<?= e($a['username']) ?></a>
              <div style="font-size:0.73rem;color:var(--text-d);"><?= time_ago($a['created_at']) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Post Answer -->
  <?php if(is_logged_in()): ?>
  <div class="card" style="margin-top:28px;">
    <div class="card-head"><span class="card-title">✏️ Your Answer</span></div>
    <div class="card-body">
      <?php if($ans_error): ?><div class="alert alert-error"><?= e($ans_error) ?></div><?php endif; ?>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="post_answer" value="1">
        <div class="form-group">
          <textarea name="body" rows="8" placeholder="Write your answer here. Wrap code in &lt;pre&gt;&lt;code&gt;...&lt;/code&gt;&lt;/pre&gt; blocks."><?= e($_POST['body']??'') ?></textarea>
          <div class="form-hint">Minimum 20 characters. Use HTML tags for formatting.</div>
        </div>
        <button type="submit" class="btn-gold">Post Answer</button>
      </form>
    </div>
  </div>
  <?php else: ?>
  <div class="card" style="margin-top:28px;padding:24px;text-align:center;">
    <p style="color:var(--text-d);margin-bottom:14px;">Login to post an answer.</p>
    <a href="login.php" class="btn-gold">Login</a>
  </div>
  <?php endif; ?>

</div>
<?php require_once 'includes/footer.php'; ?>

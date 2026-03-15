<footer class="footer">
  <div><strong>PANTHERVERSE</strong> &nbsp;|&nbsp; JRMSU Academic Community Platform</div>
  <div style="margin-top:4px;">© <?= date('Y') ?> Jose Rizal Memorial State University &nbsp;·&nbsp; College of Computing Studies &nbsp;·&nbsp; <em>Where Panther Minds Connect</em></div>
  <div style="margin-top:8px; font-size:0.85rem;">Created by: <a href="https://www.facebook.com/jhon.deguzman.56027" target="_blank" style="color:var(--gold); text-decoration:none; font-weight:700;">Jio De Guzman</a></div>
</footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Syntax highlighting
  document.querySelectorAll('pre code').forEach(el => hljs.highlightElement(el));

  // Vote buttons (Ajax)
  document.querySelectorAll('.vote-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
      const wrap = this.closest('[data-voteable]');
      if (!wrap) return;
      const [type, id] = wrap.dataset.voteable.split('-');
      const value = this.classList.contains('up') ? 1 : -1;
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      if (!csrfMeta) return;
      try {
        const res = await fetch('vote.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest'},
          body: JSON.stringify({type, id, value, csrf: csrfMeta.content})
        });
        if (res.ok) {
          const data = await res.json();
          if (data.error) {
            if (data.error.toLowerCase().includes('login')) {
              window.location.href = 'login.php';
            } else {
              alert(data.error);
            }
            return;
          }
          wrap.querySelector('.vote-count').textContent = data.vote_count;
          wrap.querySelectorAll('.vote-btn').forEach(b => b.classList.remove('active'));
          if (data.user_vote === 1)  wrap.querySelector('.vote-btn.up').classList.add('active');
          if (data.user_vote === -1) wrap.querySelector('.vote-btn.down').classList.add('active');
        }
      } catch(err) {
        console.error('Vote error:', err);
      }
    });
  });
  
  // Follow buttons (Ajax)
  document.querySelectorAll('form[action="api/follow.php"]').forEach(form => {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const res = await fetch('api/follow.php', {
        method: 'POST',
        body: formData
      });
      if (res.ok) {
        const data = await res.json();
        if (data.success) {
          const btn = this.querySelector('button');
          const userId = this.querySelector('input[name="user_id"]').value;
          if (data.following) {
            btn.textContent = '✓ Following';
            btn.className = 'btn-ghost btn-sm';
            this.querySelector('input[name="action"]').value = 'unfollow';
          } else {
            btn.textContent = '+ Follow';
            btn.className = 'btn-gold btn-sm';
            this.querySelector('input[name="action"]').value = 'follow';
          }
        }
      }
    });
  });
  
  // Like buttons (Ajax)
  document.querySelectorAll('.like-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const type = this.dataset.type;
      const id = this.dataset.id;
      const btn = document.getElementById('like-btn-' + id);
      const countSpan = document.getElementById('like-count-' + id);
      
      // Show loading state
      if (btn) btn.disabled = true;
      
      try {
        const res = await fetch('api/like.php', {
          method: 'POST',
          body: formData
        });
        
        // Get response text first for debugging
        const responseText = await res.text();
        console.log('Like API response:', responseText);
        
        // Try to parse as JSON
        let data;
        try {
          data = JSON.parse(responseText);
        } catch (e) {
          console.error('Invalid JSON response:', responseText);
          throw new Error('Invalid server response');
        }
        
        if (data.error) {
          alert(data.error);
          return;
        }
        
        if (data.success && btn && countSpan) {
          // Update button state
          if (data.liked) {
            btn.innerHTML = '<i class="bi bi-heart-fill"></i> Liked';
            btn.className = 'btn-gold btn-sm';
          } else {
            btn.innerHTML = '<i class="bi bi-heart"></i> Like';
            btn.className = 'btn-ghost btn-sm';
          }
          // Update count if available
          if (data.like_count !== undefined) {
            countSpan.textContent = data.like_count + ' likes';
          } else {
            // Fallback: increment/decrement current count
            const currentCount = parseInt(countSpan.textContent) || 0;
            countSpan.textContent = (data.liked ? currentCount + 1 : Math.max(0, currentCount - 1)) + ' likes';
          }
        }
      } catch (err) {
        console.error('Like error:', err);
        alert('Error processing like. Please try again.');
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  });

  // Bookmark toggle (Ajax)
  document.querySelectorAll('.bookmark-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const btn = this.querySelector('.bookmark-btn');
      const label = this.querySelector('.bm-label');
      if (btn) btn.disabled = true;
      try {
        const res = await fetch('api/bookmark.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.error) { alert(data.error); return; }
        if (btn && label) {
          if (data.bookmarked) {
            btn.classList.add('bookmarked');
            btn.title = 'Remove bookmark';
            label.textContent = 'Saved';
            btn.style.color = 'var(--gold)';
            btn.style.borderColor = 'var(--gold)';
          } else {
            btn.classList.remove('bookmarked');
            btn.title = 'Bookmark';
            label.textContent = 'Save';
            btn.style.color = '';
            btn.style.borderColor = '';
          }
        }
      } catch (err) {
        console.error('Bookmark error:', err);
        alert('Could not update bookmark. Please try again.');
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  });

  // Announcement dismissal
  document.querySelectorAll('.dismiss-ann').forEach(el => {
    el.addEventListener('click', function(e) {
      const id = this.dataset.id;
      const bar = document.querySelector(`.announce-bar[data-ann-id="${id}"]`);
      let dismissedMatch = document.cookie.match(/dismissed_ann=([^;]+)/);
      let ids = dismissedMatch ? dismissedMatch[1].split(',') : [];
      if (!ids.includes(id)) {
        ids.push(id);
        document.cookie = `dismissed_ann=${ids.join(',')}; path=/; max-age=604800; SameSite=Lax`;
      }
      if (this.tagName === 'BUTTON') {
        e.preventDefault();
        if (bar) {
          bar.style.opacity = '0';
          setTimeout(() => bar.style.display = 'none', 300);
        }
      }
    });
  });
});
</script>
</body>
</html>

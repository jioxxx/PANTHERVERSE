-- ============================================================
-- PANTHERVERSE — Consolidated Supabase (Postgres) Setup
-- This script creates ALL tables AND populates SEED DATA.
-- ============================================================

-- 1. CAMPUSES
CREATE TABLE IF NOT EXISTS campuses (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  location VARCHAR(255) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 2. PROGRAMS
CREATE TABLE IF NOT EXISTS programs (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 3. USERS
CREATE TABLE IF NOT EXISTS users (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(200) NOT NULL UNIQUE,
  email_verified_at TIMESTAMPTZ NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'student' CHECK (role IN ('student','instructor','admin')),
  year_level INTEGER DEFAULT 1,
  campus_id BIGINT REFERENCES campuses(id) ON DELETE SET NULL,
  program_id BIGINT REFERENCES programs(id) ON DELETE SET NULL,
  profile_photo VARCHAR(255) NULL,
  bio TEXT NULL,
  reputation INTEGER NOT NULL DEFAULT 0,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  last_seen_at TIMESTAMPTZ NULL,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 4. TAGS
CREATE TABLE IF NOT EXISTS tags (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT NULL,
  usage_count INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 5. QUESTIONS
CREATE TABLE IF NOT EXISTS questions (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(350) NOT NULL,
  body TEXT NOT NULL,
  slug VARCHAR(400) NOT NULL UNIQUE,
  status VARCHAR(20) NOT NULL DEFAULT 'open' CHECK (status IN ('open','answered','closed')),
  is_solved BOOLEAN NOT NULL DEFAULT FALSE,
  accepted_answer_id BIGINT NULL,
  view_count INTEGER NOT NULL DEFAULT 0,
  vote_count INTEGER NOT NULL DEFAULT 0,
  like_count INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

-- 6. ANSWERS
CREATE TABLE IF NOT EXISTS answers (
  id BIGSERIAL PRIMARY KEY,
  question_id BIGINT NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  body TEXT NOT NULL,
  is_accepted BOOLEAN NOT NULL DEFAULT FALSE,
  is_instructor_verified BOOLEAN NOT NULL DEFAULT FALSE,
  vote_count INTEGER NOT NULL DEFAULT 0,
  like_count INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_accepted_answer') THEN
        ALTER TABLE questions ADD CONSTRAINT fk_accepted_answer FOREIGN KEY (accepted_answer_id) REFERENCES answers(id) ON DELETE SET NULL;
    END IF;
END $$;

-- 7. FORUM
CREATE TABLE IF NOT EXISTS forum_categories (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  description TEXT NULL,
  icon VARCHAR(100) DEFAULT 'bi-chat-dots',
  display_order INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS forum_posts (
  id BIGSERIAL PRIMARY KEY,
  category_id BIGINT NOT NULL REFERENCES forum_categories(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(350) NOT NULL,
  body TEXT NOT NULL,
  is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
  is_locked BOOLEAN NOT NULL DEFAULT FALSE,
  view_count INTEGER NOT NULL DEFAULT 0,
  reply_count INTEGER NOT NULL DEFAULT 0,
  like_count INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

-- 8. CONSULTATIONS
CREATE TABLE IF NOT EXISTS instructor_availability (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  day_of_week SMALLINT NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  location VARCHAR(200) NULL,
  subject VARCHAR(150) NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS consultations (
  id BIGSERIAL PRIMARY KEY,
  student_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  instructor_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  preferred_date DATE NOT NULL,
  preferred_time TIME NOT NULL,
  status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending','approved','declined','completed','cancelled')),
  instructor_note TEXT NULL,
  question_id BIGINT NULL REFERENCES questions(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 9. STUDY GROUPS
CREATE TABLE IF NOT EXISTS study_groups (
  id BIGSERIAL PRIMARY KEY,
  owner_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  name VARCHAR(150) NOT NULL,
  subject VARCHAR(150) NOT NULL,
  description TEXT NULL,
  is_private BOOLEAN DEFAULT FALSE,
  max_members INTEGER DEFAULT 20,
  program_id BIGINT NULL REFERENCES programs(id) ON DELETE SET NULL,
  campus_id BIGINT NULL REFERENCES campuses(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS study_group_members (
  user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
  group_id BIGINT REFERENCES study_groups(id) ON DELETE CASCADE,
  role VARCHAR(20) DEFAULT 'member' CHECK (role IN ('member','moderator')),
  joined_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, group_id)
);

CREATE TABLE IF NOT EXISTS study_group_posts (
  id BIGSERIAL PRIMARY KEY,
  group_id BIGINT NOT NULL REFERENCES study_groups(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  body TEXT NOT NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

-- 10. MESSAGES
CREATE TABLE IF NOT EXISTS messages (
  id BIGSERIAL PRIMARY KEY,
  sender_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  receiver_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  body TEXT NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 11. RESOURCES
CREATE TABLE IF NOT EXISTS resources (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  file_path VARCHAR(500) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_type VARCHAR(50) NOT NULL,
  file_size BIGINT DEFAULT 0,
  download_count INTEGER NOT NULL DEFAULT 0,
  like_count INTEGER NOT NULL DEFAULT 0,
  is_instructor_verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

-- 12. ANNOUNCEMENTS
CREATE TABLE IF NOT EXISTS announcements (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(350) NOT NULL,
  body TEXT NOT NULL,
  campus_id BIGINT NULL REFERENCES campuses(id) ON DELETE SET NULL,
  priority VARCHAR(20) NOT NULL DEFAULT 'normal' CHECK (priority IN ('normal','important','urgent')),
  like_count INTEGER NOT NULL DEFAULT 0,
  expires_at TIMESTAMPTZ NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

-- 13. PROJECTS
CREATE TABLE IF NOT EXISTS projects (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  tech_stack TEXT NULL,
  repo_url VARCHAR(500) NULL,
  demo_url VARCHAR(500) NULL,
  thumbnail VARCHAR(500) NULL,
  is_endorsed BOOLEAN DEFAULT FALSE,
  like_count INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

-- 14. CALENDAR
CREATE TABLE IF NOT EXISTS calendar_events (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(250) NOT NULL,
  description TEXT NULL,
  event_date DATE NOT NULL,
  end_date DATE NULL,
  event_type VARCHAR(20) NOT NULL DEFAULT 'event' CHECK (event_type IN ('exam','deadline','holiday','event','class','other')),
  campus_id BIGINT NULL REFERENCES campuses(id) ON DELETE SET NULL,
  program_id BIGINT NULL REFERENCES programs(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 15. LIKES & ENGAGEMENT
CREATE TABLE IF NOT EXISTS likes (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  liked_id BIGINT NOT NULL,
  liked_type VARCHAR(100) NOT NULL, -- forum_post, resource, project, announcement, question, answer
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, liked_id, liked_type)
);

CREATE TABLE IF NOT EXISTS bookmarks (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  bookmarkable_id BIGINT NOT NULL,
  bookmarkable_type VARCHAR(100) NOT NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, bookmarkable_id, bookmarkable_type)
);

CREATE TABLE IF NOT EXISTS votes (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  voteable_id BIGINT NOT NULL,
  voteable_type VARCHAR(100) NOT NULL,
  value SMALLINT NOT NULL CHECK (value IN (-1, 1)),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, voteable_id, voteable_type)
);

CREATE TABLE IF NOT EXISTS comments (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  commentable_id BIGINT NOT NULL,
  commentable_type VARCHAR(100) NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

-- 16. SOCIAL
CREATE TABLE IF NOT EXISTS user_follows (
  id BIGSERIAL PRIMARY KEY,
  follower_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  following_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (follower_id, following_id)
);

CREATE TABLE IF NOT EXISTS tag_follows (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  tag_id BIGINT NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, tag_id)
);

-- 17. NOTIFICATIONS & PREFS
CREATE TABLE IF NOT EXISTS notifications (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  type VARCHAR(100) NOT NULL,
  data JSONB NOT NULL,
  read_at TIMESTAMPTZ NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notification_preferences (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE UNIQUE,
  notify_new_answer BOOLEAN DEFAULT TRUE,
  notify_answer_accepted BOOLEAN DEFAULT TRUE,
  notify_new_comment BOOLEAN DEFAULT TRUE,
  notify_new_follower BOOLEAN DEFAULT TRUE,
  notify_mention BOOLEAN DEFAULT TRUE,
  notify_consultation BOOLEAN DEFAULT TRUE,
  notify_study_group BOOLEAN DEFAULT TRUE,
  notify_message BOOLEAN DEFAULT TRUE,
  notify_announcement BOOLEAN DEFAULT TRUE,
  email_new_answer BOOLEAN DEFAULT FALSE,
  email_answer_accepted BOOLEAN DEFAULT FALSE,
  email_new_follower BOOLEAN DEFAULT FALSE,
  email_mention BOOLEAN DEFAULT FALSE,
  email_consultation BOOLEAN DEFAULT TRUE,
  email_announcement BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_preferences (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE UNIQUE,
  theme VARCHAR(20) DEFAULT 'dark' CHECK (theme IN ('dark','light','system')),
  language VARCHAR(10) DEFAULT 'en',
  email_frequency VARCHAR(20) DEFAULT 'daily' CHECK (email_frequency IN ('instant','daily','weekly','never')),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 18. LOGS & ANALYTICS
CREATE TABLE IF NOT EXISTS activity_logs (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(100) NULL,
  entity_id BIGINT NULL,
  metadata JSONB NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reputation_logs (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  amount INTEGER NOT NULL,
  reason VARCHAR(150) NOT NULL,
  source_id BIGINT NULL,
  source_type VARCHAR(100) NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 19. WIKIS & MODERATION
CREATE TABLE IF NOT EXISTS tag_wikis (
  id BIGSERIAL PRIMARY KEY,
  tag_id BIGINT NOT NULL REFERENCES tags(id) ON DELETE CASCADE UNIQUE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  content TEXT NOT NULL,
  is_approved BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS content_flags (
  id BIGSERIAL PRIMARY KEY,
  flagger_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  content_type VARCHAR(100) NOT NULL,
  content_id BIGINT NOT NULL,
  reason VARCHAR(50) NOT NULL CHECK (reason IN ('spam','harassment','inappropriate','misinformation','duplicate','other')),
  description TEXT NULL,
  status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending','reviewed','actioned','dismissed')),
  reviewed_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
  reviewed_at TIMESTAMPTZ NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 20. SUGGESTIONS
CREATE TABLE IF NOT EXISTS suggestions (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'planned', 'implemented', 'rejected')),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS suggestion_votes (
  user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
  suggestion_id BIGINT REFERENCES suggestions(id) ON DELETE CASCADE,
  PRIMARY KEY (user_id, suggestion_id)
);

-- Pivot Tables
CREATE TABLE IF NOT EXISTS question_tag (
  question_id BIGINT REFERENCES questions(id) ON DELETE CASCADE,
  tag_id BIGINT REFERENCES tags(id) ON DELETE CASCADE,
  PRIMARY KEY (question_id, tag_id)
);

CREATE TABLE IF NOT EXISTS resource_tag (
  resource_id BIGINT REFERENCES resources(id) ON DELETE CASCADE,
  tag_id BIGINT REFERENCES tags(id) ON DELETE CASCADE,
  PRIMARY KEY (resource_id, tag_id)
);

-- ============================================================
-- INITIAL SEED DATA
-- ============================================================

-- 1. CAMPUSES
INSERT INTO campuses (id, name, code, location) VALUES 
(1, 'JRMSU Main Campus', 'MAIN', 'Dapitan City'), 
(2, 'JRMSU Dipolog Campus', 'DIP', 'Dipolog City'),
(3, 'JRMSU Tampilisan Campus', 'TAMP', 'Tampilisan'),
(4, 'JRMSU Katipunan Campus', 'KAT', 'Katipunan'),
(5, 'JRMSU Siocon Campus', 'SIO', 'Siocon')
ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, code = EXCLUDED.code;

-- 2. PROGRAMS
INSERT INTO programs (id, name, code) VALUES 
(1, 'Bachelor of Science in Computer Science', 'BSCS'), 
(2, 'Bachelor of Science in Information Systems', 'BSIS'), 
(3, 'Bachelor of Science in Information Technology', 'BSIT')
ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, code = EXCLUDED.code;

-- 3. USERS (Passwords are Admin@12345, Instructor@12345, Student@12345)
INSERT INTO users (id, name, username, email, role, password, campus_id, program_id, reputation) VALUES 
(1, 'System Admin', 'admin', 'admin@pantherverse.jrmsu.edu.ph', 'admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 2, 9999),
(2, 'Prof. Maria Santos', 'prof_santos', 'msantos@pantherverse.jrmsu.edu.ph', 'instructor', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1250),
(3, 'Juan dela Cruz', 'juandc', 'juan.delacruz@pantherverse.jrmsu.edu.ph', 'student', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 350),
(4, 'Ana Reyes', 'ana_reyes', 'ana.reyes@pantherverse.jrmsu.edu.ph', 'student', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 2, 185),
(5, 'Mark Villanueva', 'markv', 'mark.villanueva@pantherverse.jrmsu.edu.ph', 'student', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 3, 90)
ON CONFLICT (id) DO NOTHING;

-- 4. TAGS
INSERT INTO tags (id, name, slug, description) VALUES 
(1, 'Java', 'java', 'Questions about Java programming'),
(2, 'Python', 'python', 'Python programming'),
(3, 'PHP', 'php', 'PHP web development'),
(4, 'Laravel', 'laravel', 'Laravel PHP framework'),
(5, 'JavaScript', 'javascript', 'JavaScript frontend and Node.js'),
(6, 'MySQL', 'mysql', 'MySQL database design'),
(7, 'HTML/CSS', 'html-css', 'Web markup and styling'),
(8, 'Algorithms', 'algorithms', 'Algorithm design and complexity')
ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, slug = EXCLUDED.slug;

-- 5. QUESTIONS
INSERT INTO questions (id, user_id, title, body, slug, status, is_solved, view_count, vote_count) VALUES 
(1, 3, 'How do I fix a NullPointerException in Java?', '<p>I keep getting a NullPointerException in my Java program when I try to call a method on an object. What causes this and how do I fix it?</p>', 'how-do-i-fix-nullpointerexception-in-java', 'answered', TRUE, 143, 12),
(2, 4, 'Difference between INNER JOIN and LEFT JOIN in MySQL?', '<p>I am designing a database and I need to retrieve student records. Should I use INNER JOIN or LEFT JOIN?</p>', 'difference-between-inner-join-and-left-join-mysql', 'answered', TRUE, 267, 18),
(3, 5, 'How does the OSI model relate to real-world protocols?', '<p>I am having trouble understanding how each layer actually maps to real protocols like HTTP, TCP, and Ethernet.</p>', 'osi-model-real-world-networking-protocols', 'open', FALSE, 89, 7)
ON CONFLICT (id) DO NOTHING;

-- 6. ANSWERS
INSERT INTO answers (id, question_id, user_id, body, is_accepted, is_instructor_verified, vote_count) VALUES 
(1, 1, 2, '<p>A NullPointerException occurs when you try to use a reference that points to no object. Always null-check before using the object.</p>', TRUE, TRUE, 14),
(2, 2, 2, '<p>INNER JOIN returns only rows where there is a match in BOTH tables. LEFT JOIN returns ALL rows from the left table and matching rows from the right table.</p>', TRUE, TRUE, 20)
ON CONFLICT (id) DO NOTHING;

-- UPDATE Question Accepted IDs
UPDATE questions SET accepted_answer_id = 1 WHERE id = 1;
UPDATE questions SET accepted_answer_id = 2 WHERE id = 2;

-- 7. FORUM CATEGORIES
INSERT INTO forum_categories (id, name, slug, icon, display_order) VALUES 
(1, 'Programming Help', 'programming-help', 'bi-code-slash', 1), 
(2, 'Database & SQL', 'database-sql', 'bi-database', 2),
(3, 'Web Development', 'web-development', 'bi-globe', 3),
(4, 'Academic Life', 'academic-life', 'bi-mortarboard', 5)
ON CONFLICT (id) DO NOTHING;

-- 8. FORUM POSTS
INSERT INTO forum_posts (id, category_id, user_id, title, body, is_pinned) VALUES 
(1, 4, 2, 'Welcome to PANTHERVERSE — Tips for New Members', '<p>Hello everyone! Let us build a strong computing community together. Go Panthers!</p>', TRUE),
(2, 1, 3, 'Best resources for learning Python?', '<p>Can anyone recommend good free resources for learning Python from scratch?</p>', FALSE)
ON CONFLICT (id) DO NOTHING;

-- 9. ANNOUNCEMENTS
INSERT INTO announcements (id, user_id, title, body, priority) VALUES 
(1, 1, 'Welcome to PANTHERVERSE!', '<p>Welcome to PANTHERVERSE — the official community platform for JRMSU computing students!</p>', 'important'),
(2, 2, 'Capstone Project Proposal Deadline', '<p>All BSIS students: Please submit your proposals by the end of the semester.</p>', 'urgent')
ON CONFLICT (id) DO NOTHING;

-- Adjust Serial values
SELECT setval('campuses_id_seq', (SELECT MAX(id) FROM campuses));
SELECT setval('programs_id_seq', (SELECT MAX(id) FROM programs));
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('tags_id_seq', (SELECT MAX(id) FROM tags));
SELECT setval('questions_id_seq', (SELECT MAX(id) FROM questions));
SELECT setval('answers_id_seq', (SELECT MAX(id) FROM answers));
SELECT setval('forum_categories_id_seq', (SELECT MAX(id) FROM forum_categories));
SELECT setval('forum_posts_id_seq', (SELECT MAX(id) FROM forum_posts));
SELECT setval('announcements_id_seq', (SELECT MAX(id) FROM announcements));

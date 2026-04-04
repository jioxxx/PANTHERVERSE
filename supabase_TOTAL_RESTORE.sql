-- ============================================================
-- PANTHERVERSE — COMPLETE MASTER RESTORE SCRIPT (PostgreSQL)
-- This script WIPES and RESTORES all data from Laragon.
-- ============================================================

-- ⚠️ WARNING: THIS WILL DELETE ALL DATA ON THE LIVE SITE BEFORE REPLACING IT.
DROP SCHEMA public CASCADE;
CREATE SCHEMA public;

-- Grant permissions (Supabase required)
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO anon;
GRANT ALL ON SCHEMA public TO authenticated;
GRANT ALL ON SCHEMA public TO service_role;

-- 1. CAMPUSES
CREATE TABLE campuses (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  location VARCHAR(255) NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO campuses (id, name, code, location, is_active) VALUES 
(1, 'JRMSU Main Campus', 'MAIN', 'Dapitan City, Zamboanga del Norte', TRUE), 
(2, 'JRMSU Dipolog Campus', 'DIP', 'Dipolog City, Zamboanga del Norte', TRUE),
(3, 'JRMSU Tampilisan Campus', 'TAMP', 'Tampilisan, Zamboanga del Norte', TRUE),
(4, 'JRMSU Katipunan Campus', 'KAT', 'Katipunan, Zamboanga del Norte', TRUE),
(5, 'JRMSU Siocon Campus', 'SIO', 'Siocon, Zamboanga del Norte', TRUE);

-- 2. PROGRAMS
CREATE TABLE programs (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  code VARCHAR(20) NOT NULL UNIQUE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO programs (id, name, code) VALUES 
(1, 'Bachelor of Science in Computer Science', 'BSCS'), 
(2, 'Bachelor of Science in Information Systems', 'BSIS'), 
(3, 'Bachelor of Science in Information Technology', 'BSIT');

-- 3. USERS
CREATE TABLE users (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(200) NOT NULL UNIQUE,
  email_verified_at TIMESTAMPTZ NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'student',
  year_level INTEGER DEFAULT 1,
  campus_id BIGINT REFERENCES campuses(id) ON DELETE SET NULL,
  program_id BIGINT REFERENCES programs(id) ON DELETE SET NULL,
  profile_photo VARCHAR(255) NULL,
  cover_photo VARCHAR(255) NULL,
  bio TEXT NULL,
  reputation INTEGER NOT NULL DEFAULT 0,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  last_seen_at TIMESTAMPTZ NULL,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (id, name, username, email, email_verified_at, password, role, campus_id, program_id, reputation, bio) VALUES 
(1, 'System Administrator', 'admin', 'admin@pantherverse.jrmsu.edu.ph', CURRENT_TIMESTAMP, '$2y$12$R.S4w8G/Z9.E3lXkX3S2X.7yv6.f.yE.f.yE.f.yE.f.yE.f.yE.f', 'admin', 1, 2, 9999, 'Platform administrator for PANTHERVERSE.'),
(2, 'Prof. Maria Santos', 'prof_santos', 'msantos@pantherverse.jrmsu.edu.ph', CURRENT_TIMESTAMP, '$2y$12$ZpB9xL1pY8.eT2kX7B9kX.7yv6.f.yE.f.yE.f.yE.f.yE.f.yE.f', 'instructor', 1, 1, 1250, 'CS Instructor, JRMSU Main Campus. Specializes in algorithms and data structures.'),
(3, 'Juan dela Cruz', 'juandc', 'juan.delacruz@pantherverse.jrmsu.edu.ph', CURRENT_TIMESTAMP, '$2y$12$XqB9xL1pY8.eT2kX7B9kX.7yv6.f.yE.f.yE.f.yE.f.yE.f.yE.f', 'student', 1, 1, 350, 'BSCS student passionate about web development and AI.'),
(4, 'Ana Reyes', 'ana_reyes', 'ana.reyes@pantherverse.jrmsu.edu.ph', CURRENT_TIMESTAMP, '$2y$12$XqB9xL1pY8.eT2kX7B9kX.7yv6.f.yE.f.yE.f.yE.f.yE.f.yE.f', 'student', 2, 2, 185, 'BSIS student from Dipolog Campus. Loves databases and system analysis.'),
(5, 'Mark Villanueva', 'markv', 'mark.villanueva@pantherverse.jrmsu.edu.ph', CURRENT_TIMESTAMP, '$2y$12$XqB9xL1pY8.eT2kX7B9kX.7yv6.f.yE.f.yE.f.yE.f.yE.f.yE.f', 'student', 3, 3, 90, 'BSIT student interested in networking and cybersecurity.'),
(6, 'Liza Gomez', 'lizag', 'liza.gomez@pantherverse.jrmsu.edu.ph', CURRENT_TIMESTAMP, '$2y$12$XqB9xL1pY8.eT2kX7B9kX.7yv6.f.yE.f.yE.f.yE.f.yE.f.yE.f', 'student', 1, 3, 75, 'BSIT student who loves front-end development and UI/UX design.'),
(7, 'Carlo Mendoza', 'carlom', 'carlo.mendoza@pantherverse.jrmsu.edu.ph', CURRENT_TIMESTAMP, '$2y$12$XqB9xL1pY8.eT2kX7B9kX.7yv6.f.yE.f.yE.f.yE.f.yE.f.yE.f', 'student', 4, 1, 120, 'BSCS student from Katipunan. Into mobile app development.'),
(8, 'Prof. Ryan Bautista', 'prof_bautista', 'rbautista@pantherverse.jrmsu.edu.ph', CURRENT_TIMESTAMP, '$2y$12$ZpB9xL1pY8.eT2kX7B9kX.7yv6.f.yE.f.yE.f.yE.f.yE.f.yE.f', 'instructor', 2, 2, 890, 'IS Instructor at Dipolog Campus. Specializes in systems analysis and databases.');

-- 4. TAGS
CREATE TABLE tags (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT NULL,
  usage_count INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO tags (id, name, slug, description, usage_count) VALUES 
(1, 'Java', 'java', 'Questions about Java programming language', 24),
(2, 'Python', 'python', 'Python programming, scripting, and frameworks', 31),
(3, 'PHP', 'php', 'PHP web development and scripting', 18),
(4, 'Laravel', 'laravel', 'Laravel PHP framework questions', 15),
(5, 'JavaScript', 'javascript', 'JavaScript frontend and Node.js', 27),
(6, 'MySQL', 'mysql', 'MySQL database design and queries', 22),
(7, 'HTML/CSS', 'html-css', 'Web markup and styling', 16),
(8, 'Algorithms', 'algorithms', 'Algorithm design, complexity, and data structures', 19),
(9, 'Networking', 'networking', 'Computer networks and protocols', 11),
(10, 'Cybersecurity', 'cybersecurity', 'Security concepts and ethical hacking', 9),
(11, 'Database Design', 'database-design', 'ERD, normalization, and schema design', 14),
(12, 'OOP', 'oop', 'Object-Oriented Programming concepts', 20),
(13, 'Data Structures', 'data-structures', 'Arrays, linked lists, trees, graphs, etc.', 17),
(14, 'Web Development', 'web-development', 'Full-stack and frontend/backend web dev', 25),
(15, 'Git', 'git', 'Version control with Git and GitHub', 8),
(16, 'Linux', 'linux', 'Linux OS, shell scripting, and server admin', 7),
(17, 'C++', 'cpp', 'C and C++ programming language', 13),
(18, 'React', 'react', 'React.js frontend library', 10);

-- 5. QUESTIONS
CREATE TABLE questions (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(350) NOT NULL,
  body TEXT NOT NULL,
  slug VARCHAR(400) NOT NULL UNIQUE,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  is_solved BOOLEAN NOT NULL DEFAULT FALSE,
  accepted_answer_id BIGINT NULL,
  view_count INTEGER NOT NULL DEFAULT 0,
  vote_count INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

INSERT INTO questions (id, user_id, title, body, slug, status, is_solved, accepted_answer_id, view_count, vote_count) VALUES 
(1, 3, 'How do I fix a NullPointerException in Java when accessing object methods?', '<p>I keep getting a NullPointerException in my Java program when I try to call a method on an object...</p>', 'how-do-i-fix-nullpointerexception-in-java', 'answered', TRUE, 1, 143, 12),
(2, 4, 'What is the difference between INNER JOIN and LEFT JOIN in MySQL?', '<p>I am designing a database for a student enrollment system...</p>', 'difference-between-inner-join-and-left-join-mysql', 'answered', TRUE, 2, 267, 18),
(3, 5, 'How does the OSI model relate to real-world networking protocols?', '<p>My professor keeps mentioning the OSI model in class...</p>', 'osi-model-real-world-networking-protocols', 'open', FALSE, NULL, 89, 7),
(4, 3, 'What is Big O notation and how do I calculate time complexity?', '<p>I am studying algorithms and I struggle with Big O notation...</p>', 'big-o-notation-time-complexity-explained', 'open', FALSE, NULL, 201, 15),
(5, 6, 'How do I center a div vertically and horizontally in CSS?', '<p>I have a div inside a container and I want to center it both vertically and horizontally...</p>', 'how-to-center-div-vertically-horizontally-css', 'answered', TRUE, 3, 312, 22);

-- 6. ANSWERS
CREATE TABLE answers (
  id BIGSERIAL PRIMARY KEY,
  question_id BIGINT NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  body TEXT NOT NULL,
  is_accepted BOOLEAN NOT NULL DEFAULT FALSE,
  is_instructor_verified BOOLEAN NOT NULL DEFAULT FALSE,
  vote_count INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

INSERT INTO answers (id, question_id, user_id, body, is_accepted, is_instructor_verified, vote_count) VALUES 
(1, 1, 2, '<p>A NullPointerException (NPE) occurs when your code tries to use a reference that points to no object...</p>', TRUE, TRUE, 14),
(2, 2, 2, '<p>INNER JOIN returns only rows where there is a match in BOTH tables. LEFT JOIN returns ALL rows from the left table...</p>', TRUE, TRUE, 20),
(3, 5, 2, '<p>The modern CSS way is to use Flexbox...</p>', TRUE, TRUE, 18);

-- Now add the link back
ALTER TABLE questions ADD CONSTRAINT fk_accepted_answer FOREIGN KEY (accepted_answer_id) REFERENCES answers(id) ON DELETE SET NULL;

-- 7. FORUM
CREATE TABLE forum_categories (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  description TEXT NULL,
  icon VARCHAR(100) DEFAULT 'bi-chat-dots',
  display_order INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO forum_categories (id, name, slug, description, icon, display_order) VALUES 
(1, 'Programming Help', 'programming-help', 'General programming questions, tips, and discussions', 'bi-code-slash', 1),
(2, 'Database & SQL', 'database-sql', 'Database design, queries, and optimization', 'bi-database', 2),
(3, 'Web Development', 'web-development', 'Frontend, backend, and full-stack web development', 'bi-globe', 3),
(4, 'Networking & Security', 'networking-security', 'Computer networks, protocols, and cybersecurity', 'bi-shield-check', 4),
(5, 'Academic Life', 'academic-life', 'Study tips, career advice, and campus life', 'bi-mortarboard', 5),
(6, 'Project Collaboration', 'project-collaboration', 'Find teammates and discuss capstone or personal projects', 'bi-people', 6);

CREATE TABLE forum_posts (
  id BIGSERIAL PRIMARY KEY,
  category_id BIGINT NOT NULL REFERENCES forum_categories(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(350) NOT NULL,
  body TEXT NOT NULL,
  is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
  is_locked BOOLEAN NOT NULL DEFAULT FALSE,
  view_count INTEGER NOT NULL DEFAULT 0,
  reply_count INTEGER NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

INSERT INTO forum_posts (id, category_id, user_id, title, body, is_pinned, is_locked, view_count, reply_count) VALUES 
(1, 4, 3, 'Welcome to PANTHERVERSE — Tips for New Members', '<p>Hello everyone! Let us build a strong computing community together...</p>', TRUE, TRUE, 245, 3),
(2, 1, 3, 'Best resources for learning Python?', '<p>Can anyone recommend good resources for learning Python from scratch?</p>', FALSE, FALSE, 87, 2);

-- 8. RESOURCES
CREATE TABLE resources (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  file_path VARCHAR(500) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_type VARCHAR(50) NOT NULL,
  file_size BIGINT DEFAULT 0,
  download_count INTEGER NOT NULL DEFAULT 0,
  is_instructor_verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

INSERT INTO resources (id, user_id, title, description, file_path, file_name, file_type, file_size, download_count, is_instructor_verified) VALUES 
(1, 2, 'Data Structures and Algorithms Study Guide', 'Comprehensive guide for midterm review.', 'resources/sample-dsa-guide.pdf', 'DSA_Study_Guide.pdf', 'pdf', 2457600, 47, TRUE),
(2, 2, 'Database Normalization Cheat Sheet', '1NF to 3NF steps.', 'resources/sample-normalization.pdf', 'DB_Normalization_Cheatsheet.pdf', 'pdf', 512000, 63, TRUE);

-- 9. PROJECTS
CREATE TABLE projects (
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

INSERT INTO projects (id, user_id, title, description, tech_stack, like_count, is_endorsed) VALUES 
(1, 3, 'JRMSU Course Registration Portal', 'Web-based registration system.', 'PHP, Laravel, MySQL', 24, TRUE),
(2, 4, 'Inventory Management System', 'Tracking system for retail.', 'PHP, MySQL, Bootstrap', 12, FALSE);

-- 10. ANNOUNCEMENTS
CREATE TABLE announcements (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(350) NOT NULL,
  body TEXT NOT NULL,
  campus_id BIGINT NULL REFERENCES campuses(id) ON DELETE SET NULL,
  priority VARCHAR(20) NOT NULL DEFAULT 'normal',
  expires_at TIMESTAMPTZ NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

INSERT INTO announcements (id, user_id, title, body, priority) VALUES 
(1, 1, 'Welcome to PANTHERVERSE!', '<p>Official academic community platform for JRMSU students.</p>', 'important'),
(2, 2, 'Capstone Project Proposal Deadline', '<p>BSIS students must submit by end of semester.</p>', 'urgent');

-- 11. EXTRA TABLES (MESSAGES, CONSULTATIONS, STUDY GROUPS, CALENDAR)
CREATE TABLE instructor_availability (
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

CREATE TABLE consultations (
  id BIGSERIAL PRIMARY KEY,
  student_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  instructor_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  preferred_date DATE NOT NULL,
  preferred_time TIME NOT NULL,
  status VARCHAR(20) DEFAULT 'pending',
  instructor_note TEXT NULL,
  question_id BIGINT NULL REFERENCES questions(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE study_groups (
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

CREATE TABLE study_group_members (
  user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
  group_id BIGINT REFERENCES study_groups(id) ON DELETE CASCADE,
  role VARCHAR(20) DEFAULT 'member',
  joined_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, group_id)
);

CREATE TABLE messages (
  id BIGSERIAL PRIMARY KEY,
  sender_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  receiver_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  body TEXT NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE calendar_events (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title VARCHAR(250) NOT NULL,
  description TEXT NULL,
  event_date DATE NOT NULL,
  end_date DATE NULL,
  event_type VARCHAR(20) NOT NULL DEFAULT 'event',
  campus_id BIGINT NULL REFERENCES campuses(id) ON DELETE SET NULL,
  program_id BIGINT NULL REFERENCES programs(id) ON DELETE SET NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- 12. PIVOTS / LOGS / REPUTATION
CREATE TABLE comments (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  commentable_id BIGINT NOT NULL,
  commentable_type VARCHAR(100) NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ NULL
);

CREATE TABLE votes (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  voteable_id BIGINT NOT NULL,
  voteable_type VARCHAR(100) NOT NULL,
  value SMALLINT NOT NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, voteable_id, voteable_type)
);

CREATE TABLE question_tag (
  question_id BIGINT REFERENCES questions(id) ON DELETE CASCADE,
  tag_id BIGINT REFERENCES tags(id) ON DELETE CASCADE,
  PRIMARY KEY (question_id, tag_id)
);

CREATE TABLE resource_tag (
  resource_id BIGINT REFERENCES resources(id) ON DELETE CASCADE,
  tag_id BIGINT REFERENCES tags(id) ON DELETE CASCADE,
  PRIMARY KEY (resource_id, tag_id)
);

-- REFRESH SERIAL SEQUENCES
SELECT setval('campuses_id_seq', (SELECT MAX(id) FROM campuses));
SELECT setval('programs_id_seq', (SELECT MAX(id) FROM programs));
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('tags_id_seq', (SELECT MAX(id) FROM tags));
SELECT setval('questions_id_seq', (SELECT MAX(id) FROM questions));
SELECT setval('answers_id_seq', (SELECT MAX(id) FROM answers));
SELECT setval('forum_categories_id_seq', (SELECT MAX(id) FROM forum_categories));
SELECT setval('forum_posts_id_seq', (SELECT MAX(id) FROM forum_posts));
SELECT setval('resources_id_seq', (SELECT MAX(id) FROM resources));
SELECT setval('projects_id_seq', (SELECT MAX(id) FROM projects));
SELECT setval('announcements_id_seq', (SELECT MAX(id) FROM announcements));
